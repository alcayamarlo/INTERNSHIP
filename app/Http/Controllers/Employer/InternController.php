<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Evaluation;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Services\ActivityLogService;
use App\Services\CompanyVisibleProfile;
use App\Services\NotificationService;
use App\Services\OjtDocumentData;
use App\Services\PlacementService;
use App\Services\WeeklyReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InternController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ActivityLogService $activityLog,
        private PlacementService $placementService,
        private WeeklyReportService $weeklyReports,
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
            ->paginate(10)
            ->withQueryString();
        $companyProfile = app(CompanyVisibleProfile::class);
        $placements->getCollection()->each(fn (Placement $placement) => $placement
            ->setAttribute('company_profile', $placement->student ? $companyProfile->summary($placement->student) : null));

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
            'journalEntries.reviewer:id,name',
            'evaluation',
            'appraisals.supervisor:id,name',
            'clearedBy:id,name',
        ]);
        $profile = $placement->student
            ? app(CompanyVisibleProfile::class)->for($placement->student, Auth::user())
            : null;

        return view('employer.interns.show', compact('placement', 'profile'));
    }

    public function updateSchedule(Request $request, Placement $placement)
    {
        $this->authorize('supervise', $placement);
        abort_unless($placement->isActive(), 403);

        $documents = app(OjtDocumentData::class);
        if ($request->filled('supervisor_name')) {
            $request->merge(['supervisor_name' => OjtDocumentData::normalizeName((string) $request->input('supervisor_name'))]);
        }
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            // "Designation" on every OJT form, from the admin's list so it is always spelled the same.
            'department' => ['nullable', Rule::in($documents->designations($placement->department))],
            'supervisor_name' => OjtDocumentData::supervisorNameRules(required: false),
        ], [
            'end_date.after' => 'The end date must be after the start date.',
            'department.in' => 'Choose a department from the list.',
            'supervisor_name.regex' => "The supervisor's name may only contain letters, spaces, periods and hyphens.",
        ]);
        if ($request->has('supervisor_name')) {
            $validated['supervisor_name'] = filled($validated['supervisor_name'] ?? null) ? $validated['supervisor_name'] : null;
        }
        foreach (['department', 'supervisor_name'] as $field) {
            if (array_key_exists($field, $validated)) {
                $documents->logPlacementChange(Auth::user(), $placement, $field, $placement->{$field}, $validated[$field]);
            }
        }

        $startChanged = ! $placement->start_date || $placement->start_date->toDateString() !== Carbon::parse($validated['start_date'])->toDateString();
        $placement->update($validated);
        if ($startChanged) {
            $this->weeklyReports->renumberUncertified($placement->fresh());
        }

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

        // Approved hours of a completed OJT back its completion; they stay fixed.
        if ($placement->isCompleted() && $attendanceLog->isApproved()) {
            return back()->with('error', 'This OJT is already complete; approved entries can no longer be changed.');
        }

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

        if ($placement->isCleared()) {
            return back()->with('error', 'This OJT is already cleared by the coordinator; time entries are locked.');
        }

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

        if ($journalEntry->isCertified()) {
            return back()->with('error', 'This report is already certified. Reopen it first if it needs changes.');
        }

        $validated = $request->validate([
            'supervisor_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        // "Certified true and correct": freezes that week's daily rows and locks the report.
        $this->weeklyReports->certify($journalEntry, Auth::user(), $validated['supervisor_comment'] ?? null);

        $this->notifyStudent($placement, 'Weekly report certified', 'Your supervisor certified your '.$journalEntry->title.' ('.$journalEntry->inclusiveDates().') as true and correct.');

        return back()->with('success', 'Report certified true and correct.');
    }

    /** Undo a mistaken certification. */
    public function reopenJournal(Placement $placement, JournalEntry $journalEntry)
    {
        $this->authorize('supervise', $placement);
        abort_unless((int) $journalEntry->placement_id === (int) $placement->id, 404);
        if (! $journalEntry->isCertified()) {
            return back()->with('error', 'This report is not certified.');
        }
        if ($placement->isCleared()) {
            return back()->with('error', 'This OJT is already cleared, so its reports are locked.');
        }

        $this->weeklyReports->reopen($journalEntry, Auth::user());

        return back()->with('success', 'Certification reopened.');
    }

    public function storeEvaluation(Request $request, Placement $placement)
    {
        $this->authorize('supervise', $placement);

        if ($placement->isCleared()) {
            return back()->with('error', 'This OJT is already cleared by the coordinator; the evaluation is locked.');
        }

        // Existing evaluations (submitted before the request step existed) stay editable;
        // only a brand-new submission requires a coordinator request first.
        if (! $placement->rating_requested_at && ! $placement->evaluation()->exists()) {
            return back()->with('error', 'Wait for the coordinator to request a rating before submitting one.');
        }

        // Evaluations submitted on the original 1-5 rubric are kept exactly as they were.
        $existing = $placement->evaluation()->first();
        if ($existing && $existing->isLegacy()) {
            return back()->with('error', 'This evaluation was submitted on the earlier 1-5 form and is kept as is. It can no longer be changed.');
        }

        // The school's Final Performance Evaluation Report (form 2).
        $rules = [
            'department_assigned' => ['required', Rule::in(app(OjtDocumentData::class)->designations($existing?->department_assigned ?? $placement->department))],
            'field_of_training' => ['required', 'string', 'max:150'],
            'supervisor_designation' => ['required', 'string', 'max:100'],
            'comments' => ['required', 'string', 'max:5000'],
            'certified_by_name' => ['nullable', 'string', 'max:150'],
            'certified_by_position' => ['nullable', 'string', 'max:100', 'required_with:certified_by_name'],
        ];
        $messages = [
            'comments.required' => 'Write the final comments / recommendations (Part IV).',
            'supervisor_designation.required' => 'Enter your designation / job title.',
            'department_assigned.in' => 'Choose a department from the list.',
            'certified_by_position.required_with' => 'Enter the position of the person who certifies the evaluation.',
        ];
        foreach (['work_scores' => Evaluation::WORK_FACTORS, 'trait_scores' => Evaluation::TRAIT_FACTORS] as $group => $factors) {
            foreach ($factors as $key => $factor) {
                $rules["{$group}.{$key}"] = ['required', 'integer', 'between:0,'.$factor['points']];
                $messages["{$group}.{$key}.required"] = "Rate {$factor['label']}.";
                $messages["{$group}.{$key}.between"] = "{$factor['label']} must be from 0 to {$factor['points']}.";
                $messages["{$group}.{$key}.integer"] = "Enter a whole number for {$factor['label']}.";
            }
        }
        $validated = $request->validate($rules, $messages);

        $work = collect(Evaluation::WORK_FACTORS)->keys()->mapWithKeys(fn ($key) => [$key => (int) $validated['work_scores'][$key]])->all();
        $traits = collect(Evaluation::TRAIT_FACTORS)->keys()->mapWithKeys(fn ($key) => [$key => (int) $validated['trait_scores'][$key]])->all();
        $workTotal = array_sum($work);
        $traitTotal = array_sum($traits);

        DB::transaction(function () use ($placement, $validated, $work, $traits, $workTotal, $traitTotal): void {
            $placement->evaluation()->updateOrCreate([], [
                'evaluator_user_id' => Auth::id(),
                'form_version' => Evaluation::FORM_SCHOOL,
                // The trainee details printed on the form, frozen at submission.
                'header_snapshot' => collect(app(OjtDocumentData::class)->values($placement))->only(OjtDocumentData::SNAPSHOT_FIELDS)->all(),
                'work_scores' => $work,
                'work_total' => $workTotal,
                'trait_scores' => $traits,
                'trait_total' => $traitTotal,
                // The legacy columns stay filled so statistics and lists keep working.
                'scores' => $work + $traits,
                'average_score' => Evaluation::averageFromTotals($workTotal, $traitTotal),
                'department_assigned' => trim($validated['department_assigned']),
                'field_of_training' => trim($validated['field_of_training']),
                // Snapshot of the job title, so the printed form keeps the title it was signed with.
                'supervisor_designation' => trim($validated['supervisor_designation']),
                'certified_by_name' => filled($validated['certified_by_name'] ?? null) ? trim($validated['certified_by_name']) : null,
                'certified_by_position' => filled($validated['certified_by_position'] ?? null) ? trim($validated['certified_by_position']) : null,
                'comments' => trim($validated['comments']),
                'strengths' => null,
                'improvements' => null,
                'would_hire' => false,
                'submitted_at' => now(),
                // A new submission goes back to the coordinator's review queue.
                'coordinator_review_status' => null,
                'coordinator_comment' => null,
                'coordinator_reviewed_at' => null,
                'coordinator_reviewed_by_user_id' => null,
            ]);

            // Fill blanks elsewhere: the trainee's department on the placement, the title on the profile.
            if (blank($placement->department)) {
                $placement->update(['department' => trim($validated['department_assigned'])]);
            }
            $employer = Auth::user()->employer;
            if ($employer && blank($employer->position)) {
                $employer->update(['position' => trim($validated['supervisor_designation'])]);
            }

            $this->activityLog->log(Auth::user(), 'evaluation_submitted', ['placement_id' => $placement->id]);
        });

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
