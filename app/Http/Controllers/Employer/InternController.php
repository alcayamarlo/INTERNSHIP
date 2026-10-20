<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Evaluation;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\PlacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InternController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ActivityLogService $activityLog,
        private PlacementService $placementService
    ) {}

    public function index(Request $request)
    {
        $employer = Auth::user()->employer;
        abort_unless($employer, 403);

        $status = $request->query('status');

        $placements = $employer->placements()
            ->with(['student.user', 'internship', 'evaluation:id,placement_id,average_score'])
            ->withApprovedHours()
            ->withCount(['attendanceLogs as pending_logs_count' => fn ($query) => $query->where('status', AttendanceLog::STATUS_PENDING)])
            ->withCount(['journalEntries as unread_journals_count' => fn ($query) => $query->where('status', JournalEntry::STATUS_SUBMITTED)])
            ->when(in_array($status, [Placement::STATUS_ACTIVE, Placement::STATUS_COMPLETED], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('employer.interns.index', compact('placements', 'status'));
    }

    public function show(Placement $placement)
    {
        $this->authorize('supervise', $placement);

        $placement->load([
            'student.user',
            'internship',
            'attendanceLogs' => fn ($query) => $query->latest('work_date'),
            'journalEntries' => fn ($query) => $query->latest('week_start'),
            'evaluation',
            'clearedBy:id,name',
        ]);

        return view('employer.interns.show', compact('placement'));
    }

    public function updateSchedule(Request $request, Placement $placement)
    {
        $this->authorize('supervise', $placement);
        abort_unless($placement->isActive(), 403);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $placement->update($validated);

        return back()->with('success', 'OJT schedule updated.');
    }

    public function reviewAttendance(Request $request, Placement $placement, AttendanceLog $attendanceLog)
    {
        $this->authorize('supervise', $placement);
        abort_unless((int) $attendanceLog->placement_id === (int) $placement->id, 404);

        if ($placement->isCleared()) {
            return back()->with('error', 'This OJT is already cleared by the coordinator; time entries are locked.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([AttendanceLog::STATUS_APPROVED, AttendanceLog::STATUS_REJECTED])],
            'reviewer_remarks' => ['nullable', 'string', 'max:1000', 'required_if:status,'.AttendanceLog::STATUS_REJECTED],
        ], [
            'reviewer_remarks.required_if' => 'Add a remark so the student knows why the entry was rejected.',
        ]);

        $attendanceLog->update([
            ...$validated,
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->activityLog->log(Auth::user(), 'attendance_reviewed', [
            'attendance_log_id' => $attendanceLog->id,
            'status' => $validated['status'],
        ]);
        $this->notifyStudent(
            $placement,
            'DTR entry '.$validated['status'],
            'Your time entry for '.$attendanceLog->work_date->format('M d, Y').' was '.$validated['status'].'.'
        );

        $completed = $validated['status'] === AttendanceLog::STATUS_APPROVED
            && $this->placementService->completeIfEligible($placement, Auth::user());

        return back()->with('success', $completed ? 'Entry approved. The OJT is now complete.' : 'DTR entry '.$validated['status'].'.');
    }

    public function approveAllAttendance(Placement $placement)
    {
        $this->authorize('supervise', $placement);

        $count = $placement->attendanceLogs()
            ->where('status', AttendanceLog::STATUS_PENDING)
            ->update([
                'status' => AttendanceLog::STATUS_APPROVED,
                'reviewed_by_user_id' => Auth::id(),
                'reviewed_at' => now(),
            ]);

        if ($count === 0) {
            return back()->with('error', 'There are no pending entries to approve.');
        }

        $this->activityLog->log(Auth::user(), 'attendance_bulk_approved', ['placement_id' => $placement->id, 'count' => $count]);
        $this->notifyStudent($placement, 'DTR entries approved', $count.' pending time entries were approved.');
        $completed = $this->placementService->completeIfEligible($placement, Auth::user());

        return back()->with('success', $count.' entries approved.'.($completed ? ' The OJT is now complete.' : ''));
    }

    public function reviewJournal(Request $request, Placement $placement, JournalEntry $journalEntry)
    {
        $this->authorize('supervise', $placement);
        abort_unless((int) $journalEntry->placement_id === (int) $placement->id, 404);

        $validated = $request->validate([
            'supervisor_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $journalEntry->update([
            'supervisor_comment' => $validated['supervisor_comment'] ?? null,
            'status' => JournalEntry::STATUS_ACKNOWLEDGED,
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->notifyStudent($placement, 'Journal acknowledged', 'Your supervisor reviewed your journal for '.$journalEntry->weekLabel().'.');

        return back()->with('success', 'Journal acknowledged.');
    }

    public function storeEvaluation(Request $request, Placement $placement)
    {
        $this->authorize('supervise', $placement);

        if ($placement->isCleared()) {
            return back()->with('error', 'This OJT is already cleared by the coordinator; the evaluation is locked.');
        }

        $rules = [
            'strengths' => ['nullable', 'string', 'max:2000'],
            'improvements' => ['nullable', 'string', 'max:2000'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'would_hire' => ['nullable', 'boolean'],
        ];
        foreach (array_keys(Evaluation::CRITERIA) as $key) {
            $rules['scores.'.$key] = ['required', 'integer', 'between:1,5'];
        }

        $validated = $request->validate($rules, [
            'scores.*.required' => 'Rate every criterion.',
        ]);

        $scores = collect(Evaluation::CRITERIA)->keys()
            ->mapWithKeys(fn ($key) => [$key => (int) $validated['scores'][$key]])
            ->all();

        DB::transaction(function () use ($placement, $validated, $scores): void {
            $placement->evaluation()->updateOrCreate([], [
                'evaluator_user_id' => Auth::id(),
                'scores' => $scores,
                'average_score' => round(array_sum($scores) / count($scores), 2),
                'strengths' => $validated['strengths'] ?? null,
                'improvements' => $validated['improvements'] ?? null,
                'comments' => $validated['comments'] ?? null,
                'would_hire' => (bool) ($validated['would_hire'] ?? false),
                'submitted_at' => now(),
            ]);

            $this->activityLog->log(Auth::user(), 'evaluation_submitted', ['placement_id' => $placement->id]);
        });

        $this->notifyStudent($placement, 'Evaluation submitted', 'Your supervisor submitted your OJT performance evaluation.');
        $completed = $this->placementService->completeIfEligible($placement, Auth::user());

        $message = $completed
            ? 'Evaluation saved. The OJT is now complete and awaits coordinator clearance.'
            : ($placement->hasMetRequiredHours() ? 'Evaluation saved.' : 'Evaluation saved. The OJT completes automatically once the required hours are approved.');

        return back()->with('success', $message);
    }

    private function notifyStudent(Placement $placement, string $title, string $message): void
    {
        $placement->loadMissing('student.user');

        if ($studentUser = $placement->student?->user) {
            $this->notificationService->send($studentUser, 'ojt_update', $title, $message, ['placement_id' => $placement->id]);
        }
    }
}
