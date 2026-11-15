<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\OjtDocumentData;
use App\Services\WeeklyReportService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OjtController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ActivityLogService $activityLog,
        private OjtDocumentData $documents,
        private WeeklyReportService $weeklyReports,
    ) {}

    public function index(Request $request)
    {
        $student = Auth::user()->student;
        abort_unless($student, 403);

        $placements = $student->placements()
            ->with(['internship', 'employer'])
            ->withApprovedHours()
            ->latest()
            ->get();

        $placement = $request->filled('placement')
            ? $placements->firstWhere('id', (int) $request->query('placement'))
            : ($placements->firstWhere('status', Placement::STATUS_ACTIVE) ?? $placements->first());

        if ($placement) {
            $placement->load([
                'attendanceLogs' => fn ($query) => $query->latest('work_date'),
                'attendanceLogs.reviewer:id,name',
                'journalEntries' => fn ($query) => $query->latest('week_start'),
                'journalEntries.reviewer:id,name',
                'appraisals.supervisor:id,name',
                'clearedBy:id,name',
            ]);
        }

        return view('student.ojt.index', compact('placements', 'placement'));
    }

    public function storeAttendance(Request $request, Placement $placement)
    {
        $this->authorize('log', $placement);

        $validated = $request->validate([
            'work_date' => [
                'required', 'date', 'before_or_equal:today',
                'after_or_equal:'.$placement->start_date->toDateString(),
                $this->uniqueDateFor($placement->attendanceLogs(), 'work_date', 'You already logged time for this date. Delete the existing entry to replace it.'),
            ],
            'time_in' => ['required', 'date_format:H:i'],
            'time_out' => ['required', 'date_format:H:i', 'after:time_in'],
            'break_minutes' => ['nullable', 'integer', 'between:0,240'],
            'task_summary' => ['required', 'string', 'max:2000'],
        ], [
            'work_date.after_or_equal' => 'The date cannot be earlier than your OJT start date.',
            'time_out.after' => 'Time out must be later than time in.',
        ]);

        $hours = AttendanceLog::computeHours($validated['time_in'], $validated['time_out'], (int) ($validated['break_minutes'] ?? 0));
        if ($hours <= 0 || $hours > 12) {
            throw ValidationException::withMessages(['time_out' => 'A single day must be between a few minutes and 12 hours after breaks.']);
        }

        $log = $placement->attendanceLogs()->create([
            ...$validated,
            'break_minutes' => (int) ($validated['break_minutes'] ?? 0),
            'hours' => $hours,
            'status' => AttendanceLog::STATUS_PENDING,
        ]);

        $this->activityLog->log(Auth::user(), 'attendance_logged', ['attendance_log_id' => $log->id]);
        $this->notifySupervisor($placement, 'New DTR entry', Auth::user()->name.' logged '.$hours.' hours for '.Carbon::parse($log->work_date)->format('M d, Y').'.');

        return back()->with('success', 'Time logged. Your supervisor will review it.');
    }

    public function destroyAttendance(Placement $placement, AttendanceLog $attendanceLog)
    {
        $this->authorize('log', $placement);
        abort_unless((int) $attendanceLog->placement_id === (int) $placement->id, 404);

        if ($attendanceLog->isApproved()) {
            return back()->with('error', 'Approved entries can no longer be deleted.');
        }

        $attendanceLog->delete();

        return back()->with('success', 'DTR entry deleted.');
    }

    public function storeJournal(Request $request, Placement $placement)
    {
        $this->authorize('log', $placement);

        $request->merge([
            'week_start' => $request->filled('week_start')
                ? Carbon::parse($request->input('week_start'))->startOfWeek()->toDateString()
                : null,
        ]);

        // Header fields the student changed on this report (only edited fields are sent), normalized
        // before validation: Title Case names, single spaces, upper-case period.
        $edits = collect((array) $request->input('header', []))
            ->only(OjtDocumentData::EDITABLE_FIELDS)
            ->map(fn ($value) => is_string($value) ? trim((string) preg_replace('/\s+/u', ' ', $value)) : $value);
        if ($edits->has('supervisor_name')) {
            $edits['supervisor_name'] = OjtDocumentData::normalizeName($edits['supervisor_name']);
        }
        if ($edits->has('period_of_evaluation')) {
            $edits['period_of_evaluation'] = mb_strtoupper((string) $edits['period_of_evaluation']);
        }
        $request->merge(['header' => $edits->all()]);

        // The school's Activity Narrative Report: three required sections; the title and the daily
        // activity table are generated (the table comes from the week's time records).
        $validated = $request->validate([
            'week_start' => [
                'required', 'date', 'before_or_equal:today',
                function (string $attribute, mixed $value, \Closure $fail) use ($placement) {
                    if ($placement->weekNumberFor(Carbon::parse($value)) === null) {
                        $fail('Choose a week on or after your OJT start date ('.$placement->start_date->format('M d, Y').').');
                    }
                },
                $this->uniqueDateFor($placement->journalEntries(), 'week_start', 'You already submitted a report for that week.'),
            ],
            'challenges' => ['required', 'string', 'min:20', 'max:5000'],
            'actions_taken' => ['required', 'string', 'min:20', 'max:5000'],
            'learnings' => ['required', 'string', 'min:20', 'max:5000'],
            'header' => ['nullable', 'array'],
            'header.company' => ['sometimes', 'required', 'string', 'min:2', 'max:150'],
            'header.supervisor_name' => ['sometimes', ...OjtDocumentData::supervisorNameRules()],
            'header.designation' => ['sometimes', 'required', Rule::in($this->documents->designations($placement->department))],
            'header.period_of_evaluation' => ['sometimes', 'required', 'string', 'min:2', 'max:40', 'regex:/^[\pL\d ().\-]+$/u'],
            'header.inclusive_dates' => [
                'sometimes', 'required', 'string', 'max:60',
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $placement) {
                    if ($request->input('week_start') && OjtDocumentData::normalizeInclusiveDates((string) $value, Carbon::parse($request->input('week_start')), $placement->start_date, $placement->end_date) === null) {
                        $fail('Inclusive dates must be days of that week (Monday to Sunday) within your OJT, e.g. "April 20 - 24, 2026".');
                    }
                },
            ],
            'save_default' => ['nullable', 'array'],
            'save_default.*' => [Rule::in(array_keys(OjtDocumentData::SAVE_AS_DEFAULT))],
        ], [
            'challenges.required' => 'Describe the difficulties you encountered this week.',
            'actions_taken.required' => 'Describe the actions you took.',
            'learnings.required' => 'Share your learning insights for the week.',
            'challenges.min' => 'Write at least 20 characters about the difficulties you encountered.',
            'actions_taken.min' => 'Write at least 20 characters about the actions you took.',
            'learnings.min' => 'Write at least 20 characters about what you learned.',
            'header.company.required' => 'Enter the company / agency name.',
            'header.supervisor_name.required' => "Enter your supervisor's name.",
            'header.supervisor_name.regex' => "The supervisor's name may only contain letters, spaces, periods and hyphens.",
            'header.supervisor_name.min' => "The supervisor's name must be at least 2 characters.",
            'header.supervisor_name.max' => "The supervisor's name may not be longer than 80 characters.",
            'header.designation.in' => 'Choose a designation from the list.',
            'header.period_of_evaluation.regex' => 'Use letters, numbers, spaces and parentheses only, e.g. "FINALS (Week 13)".',
        ]);

        $weekStart = CarbonImmutable::parse($validated['week_start']);
        $edits = $validated['header'] ?? [];
        if (isset($edits['inclusive_dates'])) {
            $edits['inclusive_dates'] = OjtDocumentData::normalizeInclusiveDates($edits['inclusive_dates'], $weekStart, $placement->start_date, $placement->end_date);
        }
        $resolved = $this->weeklyReports->resolveHeader($placement, $weekStart, $edits, $validated['save_default'] ?? [], Auth::user());

        // Never produce a report with blank trainee details.
        if ($blocked = $this->documents->blockedResponse($request, $placement, 'weekly_report', $resolved['header'])) {
            return $blocked;
        }

        $weekNumber = $placement->weekNumberFor($weekStart);
        $entry = DB::transaction(function () use ($placement, $validated, $weekNumber, $resolved) {
            // "Also update my profile / placement": the change becomes this OJT's default for later reports.
            foreach ($resolved['defaults'] as $column => $value) {
                $this->documents->logPlacementChange(Auth::user(), $placement, $column, $placement->{$column}, $value);
            }
            if ($resolved['defaults'] !== []) {
                $placement->update($resolved['defaults']);
            }

            return $placement->journalEntries()->create([
                'week_start' => $validated['week_start'],
                'challenges' => $validated['challenges'],
                'actions_taken' => $validated['actions_taken'],
                'learnings' => $validated['learnings'],
                'week_number' => $weekNumber,
                'title' => JournalEntry::titleFor($weekNumber),
                'status' => JournalEntry::STATUS_SUBMITTED,
                // Frozen now: later profile or placement changes never alter a submitted report.
                'header_snapshot' => $resolved['header'],
                'header_overrides' => $resolved['overrides'] ?: null,
            ]);
        });

        $this->activityLog->log(Auth::user(), 'journal_submitted', [
            'journal_entry_id' => $entry->id,
            'edited_fields' => array_keys($resolved['overrides']),
        ]);
        $this->notifySupervisor($placement, 'New weekly report', Auth::user()->name.' submitted a weekly report for the week of '.$entry->weekLabel().'.');

        return back()->with('success', 'Weekly report submitted. Your supervisor certifies it next.');
    }

    /**
     * My OJT details printed on the forms: the Designation (from the admin's list) and the supervisor's
     * name. Changes apply to reports not yet submitted; submitted ones keep their frozen header.
     */
    public function updateDetails(Request $request, Placement $placement)
    {
        $this->authorize('log', $placement);
        if ($request->filled('supervisor_name')) {
            $request->merge(['supervisor_name' => OjtDocumentData::normalizeName((string) $request->input('supervisor_name'))]);
        }
        $validated = $request->validate([
            'department' => ['required', Rule::in($this->documents->designations($placement->department))],
            'supervisor_name' => OjtDocumentData::supervisorNameRules(required: false),
        ], [
            'department.required' => 'Choose your designation (department).',
            'department.in' => 'Choose a designation from the list.',
            'supervisor_name.regex' => "The supervisor's name may only contain letters, spaces, periods and hyphens.",
        ]);
        $validated['supervisor_name'] = filled($validated['supervisor_name'] ?? null) ? $validated['supervisor_name'] : null;

        foreach (['department', 'supervisor_name'] as $field) {
            $this->documents->logPlacementChange(Auth::user(), $placement, $field, $placement->{$field}, $validated[$field]);
        }
        $placement->update($validated);

        return redirect()->to(route('student.ojt.index', ['placement' => $placement->id, 'tab' => 'progress']).'#ojtDetails')
            ->with('success', 'OJT details saved. They are used on your forms from now on.');
    }

    public function destroyJournal(Placement $placement, JournalEntry $journalEntry)
    {
        $this->authorize('log', $placement);
        abort_unless((int) $journalEntry->placement_id === (int) $placement->id, 404);

        // A report the coordinator returned can be replaced even after the supervisor acknowledged it.
        if ($journalEntry->isAcknowledged() && ! $journalEntry->isReturnedByCoordinator()) {
            return back()->with('error', 'Certified reports can no longer be deleted. Ask your supervisor or coordinator to reopen it.');
        }

        $journalEntry->delete();

        return back()->with('success', 'Journal entry deleted.');
    }

    /** Date-only uniqueness: stored dates may carry a time part depending on the driver. */
    private function uniqueDateFor(HasMany $relation, string $column, string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($relation, $column, $message): void {
            if ($value && (clone $relation)->whereDate($column, Carbon::parse($value)->toDateString())->exists()) {
                $fail($message);
            }
        };
    }

    private function notifySupervisor(Placement $placement, string $title, string $message): void
    {
        $placement->loadMissing('employer.user');

        if ($employerUser = $placement->employer?->user) {
            $this->notificationService->send($employerUser, 'ojt_update', $title, $message, ['placement_id' => $placement->id]);
        }
    }
}
