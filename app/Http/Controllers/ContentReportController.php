<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ContentReport;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Users report a posting, company, message, or user; administrators review it in Reported Content. */
class ContentReportController extends Controller
{
    public function __construct(private ActivityLogService $activityLog, private NotificationService $notifications) {}

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_if($user->isRole(UserRole::Admin), 403);
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(ContentReport::TYPES))],
            'id' => ['required', 'integer'],
            'reason' => ['required', Rule::in(array_keys(ContentReport::REASONS))],
            'details' => ['nullable', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Choose why you are reporting this.',
        ]);

        $target = (ContentReport::TYPES[$validated['type']])::query()->find($validated['id']);
        abort_unless($target && $this->canReport($user, $validated['type'], $target), 404);

        $alreadyOpen = ContentReport::query()
            ->where('reporter_user_id', $user->id)
            ->where('reportable_type', $validated['type'])
            ->where('reportable_id', $target->getKey())
            ->where('status', ContentReport::STATUS_OPEN)
            ->exists();
        if ($alreadyOpen) {
            return back()->with('success', 'You already reported this. An administrator will review it.');
        }

        $report = ContentReport::create([
            'reporter_user_id' => $user->id,
            'reportable_type' => $validated['type'],
            'reportable_id' => $target->getKey(),
            'reason' => $validated['reason'],
            'details' => filled($validated['details'] ?? null) ? trim($validated['details']) : null,
            'status' => ContentReport::STATUS_OPEN,
        ]);
        $this->activityLog->log($user, 'content_reported', ['report_id' => $report->id, 'type' => $report->reportable_type, 'target_id' => $report->reportable_id, 'reason' => $report->reason]);

        foreach (User::query()->where('role', UserRole::Admin->value)->where('is_active', true)->get() as $admin) {
            $this->notifications->send($admin, 'content_report', 'New report to review',
                ContentReport::TYPE_LABELS[$report->reportable_type].' reported: '.ContentReport::REASONS[$report->reason].'.', [], email: false);
        }

        return back()->with('success', 'Thanks for reporting. An administrator will review it.');
    }

    /** Users can only report what they can see; a message only by its sender or receiver, and never themselves. */
    private function canReport(User $user, string $type, $target): bool
    {
        return match ($type) {
            'message' => in_array($user->id, [(int) $target->sender_id, (int) $target->receiver_id], true) && (int) $target->sender_id !== $user->id,
            'user' => ! $user->is($target),
            'company' => (int) $target->user_id !== $user->id,
            'internship' => (int) $target->employer?->user_id !== $user->id,
            default => false,
        };
    }
}
