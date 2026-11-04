<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentReport;
use App\Services\AccountSuspension;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin queue for reported postings, companies, messages, and users. Resolving a report closes every
 * open report on the same item, tells the reporters it was reviewed, and is written to the audit log.
 */
class ModerationController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
        private NotificationService $notifications,
        private AccountSuspension $suspension,
    ) {}

    public function index(Request $request)
    {
        $status = $request->query('status') === 'resolved' ? 'resolved' : 'open';
        $type = array_key_exists((string) $request->query('type'), ContentReport::TYPES) ? $request->query('type') : null;

        $reports = ContentReport::query()
            ->with('reporter:id,name')
            ->when($status === 'open', fn ($query) => $query->where('status', ContentReport::STATUS_OPEN))
            ->when($status === 'resolved', fn ($query) => $query->where('status', '<>', ContentReport::STATUS_OPEN))
            ->when($type, fn ($query, string $type) => $query->where('reportable_type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $reports->getCollection()->each(fn (ContentReport $report) => $report->setAttribute('summary', ContentReport::describe($report->reportable_type, $report->target())));

        return view('admin.moderation.index', [
            'reports' => $reports,
            'status' => $status,
            'type' => $type,
            'openCount' => ContentReport::query()->where('status', ContentReport::STATUS_OPEN)->count(),
        ]);
    }

    public function show(ContentReport $contentReport)
    {
        $target = $contentReport->target();
        $contentReport->load('reporter:id,name,email', 'resolvedBy:id,name');

        return view('admin.moderation.show', [
            'report' => $contentReport,
            'target' => $target,
            'summary' => ContentReport::describe($contentReport->reportable_type, $target),
            'owner' => ContentReport::ownerOf($contentReport->reportable_type, $target),
            'related' => ContentReport::query()
                ->where('reportable_type', $contentReport->reportable_type)
                ->where('reportable_id', $contentReport->reportable_id)
                ->whereKeyNot($contentReport->id)
                ->with('reporter:id,name')
                ->latest()
                ->get(),
            'canHide' => in_array($contentReport->reportable_type, ['internship', 'message'], true) && $target !== null,
        ]);
    }

    public function resolve(Request $request, ContentReport $contentReport)
    {
        abort_unless($contentReport->isOpen(), 409, 'This report was already resolved.');
        $validated = $request->validate([
            'resolution' => ['required', Rule::in(array_keys(ContentReport::RESOLUTIONS))],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'warning' => ['nullable', 'string', 'max:1000', 'required_if:resolution,warn'],
            'suspension_reason' => ['nullable', 'string', 'max:1000', 'required_if:resolution,suspend'],
            'suspended_until' => ['nullable', 'date', 'after:now'],
        ], [
            'warning.required_if' => 'Write the warning the owner will receive.',
            'suspension_reason.required_if' => 'Give a reason. The owner sees it when they try to sign in.',
        ]);

        $target = $contentReport->target();
        $owner = ContentReport::ownerOf($contentReport->reportable_type, $target);
        $resolution = $validated['resolution'];

        try {
            match ($resolution) {
                'hide' => $this->hide($contentReport, $target),
                'warn' => $this->warn($owner, $validated['warning']),
                'suspend' => $owner
                    ? $this->suspension->suspend($owner, Auth::user(), $validated['suspension_reason'],
                        isset($validated['suspended_until']) ? Carbon::parse($validated['suspended_until'], 'Asia/Manila') : null,
                        ['report_id' => $contentReport->id])
                    : throw ValidationException::withMessages(['resolution' => 'There is no account to suspend.']),
                default => null,
            };
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        // Close every open report on the same item with the same outcome.
        $reports = ContentReport::query()
            ->where('reportable_type', $contentReport->reportable_type)
            ->where('reportable_id', $contentReport->reportable_id)
            ->where('status', ContentReport::STATUS_OPEN)
            ->get();
        foreach ($reports as $report) {
            $report->update([
                'status' => $resolution === 'dismiss' ? ContentReport::STATUS_DISMISSED : ContentReport::STATUS_ACTIONED,
                'resolution' => $resolution,
                'admin_notes' => $validated['admin_notes'] ?? null,
                'resolved_by_user_id' => Auth::id(),
                'resolved_at' => now(),
            ]);
            if ($report->reporter) {
                $this->notifications->send($report->reporter, 'content_report', 'Your report was reviewed',
                    $resolution === 'dismiss'
                        ? 'An administrator reviewed your report and found no violation. Thank you for letting us know.'
                        : 'An administrator reviewed your report and took action. Thank you for helping keep SkillBridge safe.',
                    [], email: false);
            }
        }

        $this->activityLog->log(Auth::user(), 'report_resolved', [
            'report_ids' => $reports->modelKeys(),
            'type' => $contentReport->reportable_type,
            'target_id' => $contentReport->reportable_id,
            'resolution' => $resolution,
            'owner_user_id' => $owner?->id,
        ]);

        return redirect()->route('admin.moderation.index')->with('success', 'Report resolved: '.strtolower(ContentReport::RESOLUTIONS[$resolution]).'.');
    }

    private function hide(ContentReport $report, $target): void
    {
        if (! $target || ! in_array($report->reportable_type, ['internship', 'message'], true)) {
            throw ValidationException::withMessages(['resolution' => 'Only postings and messages can be hidden.']);
        }

        $report->reportable_type === 'internship'
            ? $target->update(['hidden_at' => now(), 'hidden_reason' => ContentReport::REASONS[$report->reason] ?? 'Reported content'])
            : $target->update(['hidden_at' => now()]);
        $this->activityLog->log(Auth::user(), 'content_hidden', ['type' => $report->reportable_type, 'target_id' => $target->getKey()]);

        if ($owner = ContentReport::ownerOf($report->reportable_type, $target)) {
            $this->notifications->send($owner, 'moderation', 'Content removed by an administrator',
                ($report->reportable_type === 'internship' ? "Your posting \"{$target->title}\"" : 'One of your messages').' was hidden after a report: '.(ContentReport::REASONS[$report->reason] ?? 'policy violation').'.');
        }
    }

    private function warn($owner, string $warning): void
    {
        if (! $owner) {
            throw ValidationException::withMessages(['resolution' => 'There is no account to warn.']);
        }
        $this->notifications->send($owner, 'moderation', 'Warning from an administrator', $warning);
        $this->activityLog->log(Auth::user(), 'user_warned', ['user_id' => $owner->id]);
    }
}
