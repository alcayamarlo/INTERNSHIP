<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Placement;
use App\Services\ActivityLogService;
use App\Services\AppraisalService;
use App\Services\OjtReminderService;
use App\Services\PlacementService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * OJT Monitoring > "Requests and reminders": request a document for every ongoing OJT (once per period) and
 * remind whoever's turn it is (no limit), plus the per-student "Remind to log time".
 */
class OjtRequestController extends Controller
{
    public function __construct(private OjtReminderService $reminders) {}

    /** Monthly evaluation (a month) or final evaluation, for every ongoing OJT of the coordinator's department. */
    public function request(Request $request, AppraisalService $appraisals, PlacementService $placements, ActivityLogService $activityLog): RedirectResponse
    {
        abort_unless(Auth::user()->coordinator, 403);
        $validated = $request->validate([
            'type' => ['required', Rule::in(['monthly', 'final'])],
            'period_month' => ['exclude_unless:type,monthly', 'required', 'date_format:Y-m'],
            'due_on' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'due_on.after_or_equal' => 'The due date cannot be in the past.',
            'period_month.required' => 'Choose the month to evaluate.',
        ]);

        if ($validated['type'] === 'monthly') {
            $month = CarbonImmutable::createFromFormat('Y-m-d', $validated['period_month'].'-01');
            $result = $appraisals->bulkRequest(Auth::user(), $month, CarbonImmutable::parse($validated['due_on']));
            $what = 'Monthly evaluation for '.$month->format('F Y');
            $skipReason = 'already requested, or the month is outside the OJT dates';
        } else {
            $result = $placements->requestFinalForAll(Auth::user(), $validated['due_on']);
            $what = 'Final evaluation';
            $skipReason = 'already requested or already evaluated';
        }
        $activityLog->log(Auth::user(), 'coordinator_bulk_request', ['type' => $validated['type'], ...$result]);

        return back()->with('success', "{$what} requested for {$result['created']} OJT(s)."
            .($result['skipped'] ? " Skipped {$result['skipped']} ({$skipReason}); use Remind to nudge them." : ''));
    }

    /** Remind everyone whose turn it is. No limit: the route only guards against floods. */
    public function remindAll(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->coordinator, 403);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(OjtReminderService::TYPES))],
            'audience' => ['required', Rule::in(['students', 'supervisors'])],
        ]);
        $type = $data['type'];
        $audience = $data['audience'];

        if ($type === 'daily') {
            if ($audience === 'supervisors') {
                return back()->with('status', 'Daily time logs are only the student\'s to fill in, so there is no supervisor reminder for them.');
            }

            $result = $this->reminders->remindDailyLogs(Auth::user(), 'manual', Auth::user());
            $waiting = $result['waiting']
                ? ' '.$result['waiting'].' student'.($result['waiting'] === 1 ? ' was' : 's were').' reminded in the last '.OjtReminderService::waitHours().' hours and can be reminded again later.'
                : '';

            return back()->with($result['sent'] || $result['waiting'] ? 'success' : 'status', $result['sent'] || $result['waiting']
                ? 'Reminded '.$result['sent'].' student'.($result['sent'] === 1 ? '' : 's').' to log today\'s time.'.$waiting
                : 'Nobody to remind: every ongoing OJT has logged time today (or today is not a work day).');
        }

        $counts = $this->reminders->remindAll(Auth::user(), $type, $audience);
        $label = mb_strtolower(OjtReminderService::TYPES[$type][0]);
        $sent = $counts[$audience];
        if ($sent === 0) {
            return back()->with('status', "Nobody to remind about the {$label}: nothing is waiting on a ".rtrim($audience, 's').'.');
        }

        return back()->with('success', 'Reminded '.$sent.' '.($sent === 1 ? rtrim($audience, 's') : $audience)." about the {$label}.");
    }

    /** One student: "Remind to log time", at most once every 8 hours. */
    public function remindDailyLog(Placement $placement): RedirectResponse
    {
        $this->authorize('view', $placement);
        abort_unless(Auth::user()->coordinator, 403);
        $placement->loadMissing('student.user');
        $name = $placement->student?->user?->name ?? 'The student';

        return match ($this->reminders->remindDailyLog($placement, 'manual', Auth::user())) {
            'sent' => back()->with('success', "Reminded {$name} to log today's time."),
            'logged' => back()->with('status', "{$name} already logged time today."),
            'waiting' => back()->with('error', "{$name} was reminded less than ".OjtReminderService::waitHours().' hours ago. You can remind again after '
                .($this->reminders->nextDailyReminderAt($placement)?->format('g:i A') ?? 'a while').'.'),
            'failed' => back()->with('error', 'The reminder could not be sent. Please try again.'),
            default => back()->with('status', "No reminder needed: {$name} is not expected to log time today (weekend, or outside the OJT dates)."),
        };
    }
}
