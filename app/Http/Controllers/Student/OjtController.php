<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OjtController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ActivityLogService $activityLog
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
                'evaluation.evaluator:id,name',
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
        if ($hours <= 0 || $hours > 16) {
            throw ValidationException::withMessages(['time_out' => 'A single day must be between a few minutes and 16 hours after breaks.']);
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

        $validated = $request->validate([
            'week_start' => [
                'required', 'date', 'before_or_equal:today',
                $this->uniqueDateFor($placement->journalEntries(), 'week_start', 'You already submitted a journal for that week.'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'accomplishments' => ['required', 'string', 'max:5000'],
            'learnings' => ['nullable', 'string', 'max:5000'],
            'challenges' => ['nullable', 'string', 'max:5000'],
        ]);

        $entry = $placement->journalEntries()->create([
            ...$validated,
            'status' => JournalEntry::STATUS_SUBMITTED,
        ]);

        $this->activityLog->log(Auth::user(), 'journal_submitted', ['journal_entry_id' => $entry->id]);
        $this->notifySupervisor($placement, 'New weekly journal', Auth::user()->name.' submitted a journal for the week of '.$entry->weekLabel().'.');

        return back()->with('success', 'Weekly journal submitted.');
    }

    public function destroyJournal(Placement $placement, JournalEntry $journalEntry)
    {
        $this->authorize('log', $placement);
        abort_unless((int) $journalEntry->placement_id === (int) $placement->id, 404);

        if ($journalEntry->isAcknowledged()) {
            return back()->with('error', 'Acknowledged journals can no longer be deleted.');
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
