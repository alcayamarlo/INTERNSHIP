<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Evaluation;
use App\Models\JournalEntry;
use App\Services\ActivityLogService;
use App\Services\EvaluationProgress;
use App\Services\NotificationService;
use App\Services\WeeklyReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * The coordinator's second review, after the company supervisor: weekly reports (once acknowledged)
 * and supervisor evaluations are marked Reviewed or Returned with a comment. Attendance stays the
 * supervisor's call and is listed here for follow-up.
 */
class ReviewController extends Controller
{
    public const REVIEWED = 'reviewed';

    public const RETURNED = 'returned';

    public const REPORT_FILTERS = [
        'waiting' => 'Waiting for you',
        'supervisor' => 'Waiting for supervisor',
        'reviewed' => 'Reviewed',
        'returned' => 'Returned',
        'all' => 'All',
    ];

    public function __construct(private ActivityLogService $activityLog, private NotificationService $notifications, private EvaluationProgress $progress) {}

    public function index(Request $request)
    {
        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator, 403);
        $requestedTab = $request->query('tab') === 'attendance' ? 'logs' : $request->query('tab'); // old links
        // Tabs: logs, reports, monthly, final. Monthly and final share the evaluations code; 'evaluations' is the old link.
        $requestedTab = in_array($requestedTab, ['monthly', 'final'], true) ? 'evaluations' : $requestedTab;
        $tab = in_array($requestedTab, ['reports', 'evaluations', 'logs'], true) ? $requestedTab : 'reports';
        $inScope = fn ($query) => $query->whereIn('student_id', $coordinator->scopedStudentsQuery()->select('id'));

        // Weekly reports: what waits for this coordinator by default, but reports already handled, returned or
        // still with the supervisor can be listed too (they used to vanish from the page).
        $reportScope = JournalEntry::query()->whereHas('placement', $inScope);
        $reports = (clone $reportScope)->where('status', JournalEntry::STATUS_ACKNOWLEDGED)->whereNull('coordinator_review_status');
        $show = array_key_exists((string) $request->query('show'), self::REPORT_FILTERS) ? $request->query('show') : 'waiting';
        $reportList = match ($show) {
            'supervisor' => (clone $reportScope)->where('status', JournalEntry::STATUS_SUBMITTED),
            'reviewed' => (clone $reportScope)->where('coordinator_review_status', self::REVIEWED),
            'returned' => (clone $reportScope)->where('coordinator_review_status', self::RETURNED),
            'all' => (clone $reportScope)->whereIn('status', [JournalEntry::STATUS_SUBMITTED, JournalEntry::STATUS_ACKNOWLEDGED]),
            default => clone $reports,
        };
        $evaluations = Evaluation::query()->whereHas('placement', $inScope)->whereNull('coordinator_review_status');
        // Daily logs: every time entry of the department's students (not just the last 14 days), filterable.
        $logScope = AttendanceLog::query()->whereHas('placement', $inScope);
        $logStatus = in_array($request->query('log_status'), [AttendanceLog::STATUS_PENDING, AttendanceLog::STATUS_APPROVED, AttendanceLog::STATUS_REJECTED], true) ? $request->query('log_status') : null;
        $from = $this->dateOrNull($request->query('from'));
        $to = $this->dateOrNull($request->query('to'));
        $logs = (clone $logScope)
            ->when($logStatus, fn ($q) => $q->where('status', $logStatus))
            ->when($from, fn ($q) => $q->whereDate('work_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('work_date', '<=', $to));

        // Evaluations tab: every requested evaluation and whose turn it is (monthly and final), plus the
        // final evaluations the supervisor submitted that wait for this coordinator's review.
        $type = ($request->query('tab') === 'final' || $request->query('type') === EvaluationProgress::TYPE_FINAL) ? EvaluationProgress::TYPE_FINAL : EvaluationProgress::TYPE_MONTHLY;
        $status = array_key_exists((string) $request->query('status'), EvaluationProgress::STATUSES) ? $request->query('status') : null;
        $progress = $tab === 'evaluations'
            ? ($type === EvaluationProgress::TYPE_FINAL ? $this->progress->final($coordinator, $status) : $this->progress->monthly($coordinator, $status))
            : null;

        return view('coordinator.reviews.index', [
            'tab' => $tab,
            'progress' => $progress,
            'progressType' => $type,
            'progressStatus' => $status,
            'show' => $show,
            'reportFilters' => self::REPORT_FILTERS,
            'logStatus' => $logStatus,
            'from' => $from,
            'to' => $to,
            'counts' => [
                'reports' => (clone $reports)->count(),
                'evaluations' => $this->progress->waitingForCoordinator($coordinator),
                'logs' => (clone $logScope)->where('status', AttendanceLog::STATUS_PENDING)->count(),
            ],
            'items' => match ($tab) {
                'evaluations' => $evaluations->with(['placement.student.user', 'placement.employer', 'evaluator:id,name'])->latest('submitted_at')->paginate(10, ['*'], 'review_page')->withQueryString(),
                'logs' => $logs->with(['placement.student.user', 'placement.employer', 'reviewer:id,name'])->latest('work_date')->paginate(15)->withQueryString(),
                default => $reportList->with(['placement.student.user', 'placement.employer'])->latest('week_start')->paginate(10)->withQueryString(),
            },
        ]);
    }

    private function dateOrNull(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
    }

    /** "Remind": nudges whoever's turn it is on one evaluation, each time it is clicked. */
    public function remind(Request $request): RedirectResponse
    {
        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator, 403);
        $validated = $request->validate([
            'type' => ['required', Rule::in([EvaluationProgress::TYPE_MONTHLY, EvaluationProgress::TYPE_FINAL])],
            'id' => ['required', 'integer'],
        ]);

        $name = $this->progress->remind($validated['type'], (int) $validated['id'], $coordinator);

        return back()->with($name ? 'success' : 'error', $name
            ? "Reminder sent to {$name}."
            : 'No reminder sent: it is not the student\'s or supervisor\'s turn on this evaluation.');
    }

    public function reviewJournal(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('view', $journalEntry->placement);
        $validated = $this->validated($request);

        $journalEntry->update($this->reviewAttributes($validated));
        $this->activityLog->log(Auth::user(), 'journal_'.$validated['decision'], ['journal_entry_id' => $journalEntry->id]);

        if ($validated['decision'] === self::RETURNED && $studentUser = $journalEntry->placement->student?->user) {
            $this->notifications->send($studentUser, 'ojt_update', 'Weekly report returned',
                "Your coordinator returned your report \"{$journalEntry->title}\": {$validated['comment']}",
                ['placement_id' => $journalEntry->placement_id, 'section' => \App\Services\NotificationTarget::WEEKLY_REPORT]);
        }

        return back()->with('success', $validated['decision'] === self::RETURNED ? 'Report returned to the student.' : 'Report marked reviewed.');
    }

    /** Undo a supervisor's "certified true and correct" on a weekly report (e.g. certified by mistake). */
    public function reopenJournal(JournalEntry $journalEntry, WeeklyReportService $weeklyReports): RedirectResponse
    {
        $this->authorize('view', $journalEntry->placement);
        if (! $journalEntry->isCertified()) {
            return back()->with('error', 'This report is not certified.');
        }
        if ($journalEntry->placement->isCleared()) {
            return back()->with('error', 'This OJT is already cleared, so its reports are locked.');
        }

        $weeklyReports->reopen($journalEntry, Auth::user());

        return back()->with('success', 'Certification reopened. The supervisor can certify it again.');
    }

    public function reviewEvaluation(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorize('view', $evaluation->placement);
        $validated = $this->validated($request);

        $evaluation->update($this->reviewAttributes($validated));
        $this->activityLog->log(Auth::user(), 'evaluation_'.$validated['decision'], ['evaluation_id' => $evaluation->id]);

        if ($validated['decision'] === self::RETURNED && $employerUser = $evaluation->placement->supervisingUser()) {
            $this->notifications->send($employerUser, 'ojt_update', 'Evaluation returned',
                'The coordinator returned the evaluation for '.($evaluation->placement->student?->user?->name ?? 'your intern').': '.$validated['comment'],
                ['placement_id' => $evaluation->placement_id, 'section' => \App\Services\NotificationTarget::FINAL_EVALUATION]);
        }

        return back()->with('success', $validated['decision'] === self::RETURNED ? 'Evaluation returned to the company.' : 'Evaluation marked reviewed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'decision' => ['required', Rule::in([self::REVIEWED, self::RETURNED])],
            'comment' => ['nullable', 'string', 'max:1000', 'required_if:decision,'.self::RETURNED],
        ], [
            'comment.required_if' => 'Add a comment explaining what needs to change.',
        ]);
    }

    private function reviewAttributes(array $validated): array
    {
        return [
            'coordinator_review_status' => $validated['decision'],
            'coordinator_comment' => filled($validated['comment'] ?? null) ? trim($validated['comment']) : null,
            'coordinator_reviewed_at' => now(),
            'coordinator_reviewed_by_user_id' => Auth::id(),
        ];
    }
}
