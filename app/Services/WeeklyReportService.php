<?php

namespace App\Services;

use App\Models\AcademicSetting;
use App\Models\AttendanceLog;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The weekly Activity Narrative Report: the Monday-to-Friday activity table comes from the student's
 * time records, and is frozen into daily_snapshot when the supervisor certifies the report, so later
 * attendance edits can never change a signed document.
 */
class WeeklyReportService
{
    public function __construct(
        private ActivityLogService $activityLog,
        private NotificationService $notifications,
    ) {}

    /**
     * Mon-Fri rows: the frozen snapshot once certified, otherwise built from the time records.
     *
     * @return list<array{date: string, date_label: string, day: string, activity: string, minutes: int, hours_label: string}>
     */
    public function dailyRows(JournalEntry $entry): array
    {
        return is_array($entry->daily_snapshot) && $entry->daily_snapshot !== []
            ? $entry->daily_snapshot
            : $this->liveRows($entry);
    }

    /** @return list<array{date: string, date_label: string, day: string, activity: string, minutes: int, hours_label: string}> */
    public function liveRows(JournalEntry $entry): array
    {
        $monday = CarbonImmutable::parse($entry->week_start)->startOfWeek();
        $friday = $monday->addDays(4);
        // Rejected entries are not part of the record; pending and approved ones are.
        $logs = AttendanceLog::query()
            ->where('placement_id', $entry->placement_id)
            ->whereDate('work_date', '>=', $monday->toDateString())
            ->whereDate('work_date', '<=', $friday->toDateString())
            ->where('status', '!=', AttendanceLog::STATUS_REJECTED)
            ->get()
            ->keyBy(fn (AttendanceLog $log) => $log->work_date->toDateString());

        $rows = [];
        for ($day = $monday; $day->lte($friday); $day = $day->addDay()) {
            $log = $logs->get($day->toDateString());
            $minutes = $log ? (int) round(((float) $log->hours) * 60) : 0;
            $rows[] = [
                'date' => $day->toDateString(),
                'date_label' => $day->format('F j, Y'),
                'day' => $day->format('D'),
                'activity' => $log ? trim((string) $log->task_summary) : '',
                'minutes' => $minutes,
                'hours_label' => self::formatMinutes($minutes),
            ];
        }

        return $rows;
    }

    /** "8 hrs and 3 mins", "8 hrs", "1 hr and 1 min", "42 mins", or "-" for none. */
    public static function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '-';
        }
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;
        $hourText = $hours > 0 ? $hours.' '.($hours === 1 ? 'hr' : 'hrs') : '';
        $minuteText = $rest > 0 ? $rest.' '.($rest === 1 ? 'min' : 'mins') : '';

        return $hourText !== '' && $minuteText !== '' ? "{$hourText} and {$minuteText}" : ($hourText ?: $minuteText);
    }

    /** "FINALS (Week 13)": the month's evaluation period name for the week's Monday, plus the week number. */
    public function periodLabel(JournalEntry $entry): string
    {
        $monday = CarbonImmutable::parse($entry->week_start)->startOfWeek();
        $label = mb_strtoupper(AcademicSetting::current()->evaluationPeriodLabelFor($monday->month));

        return $entry->week_number ? "{$label} (Week {$entry->week_number})" : $label;
    }

    /** "Certified true and correct": freezes the daily rows and locks the report for the student. */
    public function certify(JournalEntry $entry, User $supervisor, ?string $comment): JournalEntry
    {
        DB::transaction(function () use ($entry, $supervisor, $comment) {
            $locked = JournalEntry::query()->lockForUpdate()->findOrFail($entry->id);
            $locked->update([
                'supervisor_comment' => filled($comment) ? trim((string) $comment) : null,
                'status' => JournalEntry::STATUS_ACKNOWLEDGED,
                'reviewed_by_user_id' => $supervisor->id,
                'reviewed_at' => now(),
                'daily_snapshot' => $this->liveRows($locked),
            ]);
        });
        $entry->refresh();
        $this->activityLog->log($supervisor, 'journal_certified', ['journal_entry_id' => $entry->id]);

        return $entry;
    }

    /** Undo a certification (supervisor or coordinator): the report is editable again and the snapshot is dropped. */
    public function reopen(JournalEntry $entry, User $actor): JournalEntry
    {
        $entry->update([
            'status' => JournalEntry::STATUS_SUBMITTED,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'daily_snapshot' => null,
        ]);
        $this->activityLog->log($actor, 'journal_certification_reopened', ['journal_entry_id' => $entry->id]);

        $entry->loadMissing('placement.student.user');
        if ($user = $entry->placement?->student?->user) {
            $this->notifications->send($user, 'ojt_update', 'Weekly report reopened',
                'Your '.$entry->title.' certification was reopened. You can now delete and resubmit it if changes are needed.',
                ['placement_id' => $entry->placement_id]);
        }

        return $entry;
    }

    /** Renumbers reports that are not certified yet, e.g. after the OJT start date changes. */
    public function renumberUncertified(Placement $placement): void
    {
        $placement->journalEntries()->where('status', '!=', JournalEntry::STATUS_ACKNOWLEDGED)->get()
            ->each(function (JournalEntry $entry) use ($placement) {
                $number = $placement->weekNumberFor($entry->week_start);
                $taken = $number !== null && $placement->journalEntries()->where('id', '!=', $entry->id)->where('week_number', $number)->exists();
                if (! $taken) {
                    $entry->update(['week_number' => $number, 'title' => JournalEntry::titleFor($number)]);
                }
            });
    }
}
