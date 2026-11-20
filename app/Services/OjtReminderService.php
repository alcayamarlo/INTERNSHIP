<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Reminders the coordinator sends from OJT Monitoring, and the automatic daily time record reminder.
 *
 * - Daily time record: a student with an ongoing OJT who has not logged today (a weekday within the OJT dates).
 *   Automatic once a day at config('ojt_reminders.daily_log_remind_at') (8 hours into a 08:00 work day), or by the
 *   coordinator. A student is never reminded about it twice within config('ojt_reminders.daily_log_wait_hours').
 * - Weekly report, monthly evaluation, final evaluation: everyone whose turn it is (OjtDeadlineService), as often as
 *   the coordinator clicks. Each person gets one notification listing their items, not one per item.
 *
 * Every daily-log reminder is recorded in ojt_reminders (kind daily_log), which also drives the 8-hour wait.
 */
class OjtReminderService
{
    public const DAILY_LOG = 'daily_log';

    /** Panel type => [label, OjtDeadlineService kind, NotificationTarget section, sidebar overview page] */
    public const TYPES = [
        'daily' => ['Daily time record', null, NotificationTarget::TIME_RECORD, 'time-records'],
        'weekly' => ['Weekly report', OjtDeadlineService::WEEKLY_REPORT, NotificationTarget::WEEKLY_REPORT, 'weekly-reports'],
        'monthly' => ['Monthly evaluation', OjtDeadlineService::MONTHLY_EVALUATION, NotificationTarget::MONTHLY_EVALUATION, 'monthly-evaluations'],
        'final' => ['Final evaluation', OjtDeadlineService::FINAL_EVALUATION, NotificationTarget::FINAL_EVALUATION, 'final-evaluations'],
    ];

    public function __construct(
        private NotificationService $notifications,
        private OjtDeadlineService $deadlines,
        private ActivityLogService $activityLog,
    ) {}

    public static function waitHours(): int
    {
        return max(1, (int) config('ojt_reminders.daily_log_wait_hours', 8));
    }

    /** Ongoing OJTs, limited to the coordinator's department when one is given. */
    public function activePlacements(?User $coordinator): Builder
    {
        $profile = $coordinator?->coordinator;

        return Placement::query()
            ->where('status', Placement::STATUS_ACTIVE)
            ->when($coordinator !== null, fn (Builder $query) => $profile
                ? $query->whereIn('student_id', $profile->scopedStudentsQuery()->select('id'))
                : $query->whereRaw('0 = 1'));
    }

    // ------------------------------------------------------------------ daily time record

    /** Ongoing OJTs expected to log today (a weekday within the OJT dates) that have no time record for today. */
    public function notLoggedToday(?User $coordinator = null): Collection
    {
        $today = OjtDeadlineService::today();
        if ($today->isWeekend()) {
            return collect();
        }

        return $this->activePlacements($coordinator)
            ->whereDate('start_date', '<=', $today->toDateString())
            ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today->toDateString()))
            ->whereDoesntHave('attendanceLogs', fn (Builder $logs) => $logs->whereDate('work_date', $today->toDateString()))
            ->with('student.user')
            ->get();
    }

    /** When this student was last reminded to log time (automatic or by a coordinator). */
    public function lastDailyReminderAt(Placement $placement): ?CarbonImmutable
    {
        $sentAt = DB::table('ojt_reminders')->where('placement_id', $placement->id)->where('kind', self::DAILY_LOG)->max('sent_at');

        return $sentAt ? CarbonImmutable::parse($sentAt) : null;
    }

    /** The earliest time another daily-log reminder may go out, or null when it may go out now. */
    public function nextDailyReminderAt(Placement $placement): ?CarbonImmutable
    {
        $next = $this->lastDailyReminderAt($placement)?->addHours(self::waitHours());

        return $next && $next->isFuture() ? $next : null;
    }

    /**
     * For the OJT Monitoring table: per placement id, whether it logged today and when it was last reminded.
     *
     * @param  iterable<Placement>  $placements
     * @return array<int, array{expected: bool, logged: bool, last: ?CarbonImmutable, next: ?CarbonImmutable}>
     */
    public function dailyLogState(iterable $placements): array
    {
        $today = OjtDeadlineService::today();
        $active = collect($placements)->filter(fn (Placement $placement) => $placement->isActive())->keyBy('id');
        if ($active->isEmpty()) {
            return [];
        }
        $ids = $active->keys()->all();
        $logged = AttendanceLog::query()->whereIn('placement_id', $ids)->whereDate('work_date', $today->toDateString())->pluck('placement_id')->flip();
        $last = DB::table('ojt_reminders')->whereIn('placement_id', $ids)->where('kind', self::DAILY_LOG)
            ->groupBy('placement_id')->selectRaw('placement_id, max(sent_at) as last_sent')->pluck('last_sent', 'placement_id');

        return $active->map(function (Placement $placement) use ($today, $logged, $last) {
            $lastAt = isset($last[$placement->id]) ? CarbonImmutable::parse($last[$placement->id]) : null;
            $next = $lastAt?->addHours(self::waitHours());

            return [
                'expected' => ! $today->isWeekend() && $placement->start_date && $placement->start_date->lte($today)
                    && (! $placement->end_date || $placement->end_date->gte($today)),
                'logged' => isset($logged[$placement->id]),
                'last' => $lastAt,
                'next' => $next && $next->isFuture() ? $next : null,
            ];
        })->all();
    }

    /**
     * Reminds one student to log today's time. Returns 'sent', 'logged' (already logged today), 'not_expected'
     * (weekend, outside the OJT dates, not ongoing) or 'waiting' (reminded less than 8 hours ago).
     */
    public function remindDailyLog(Placement $placement, string $how, ?User $by = null): string
    {
        $today = OjtDeadlineService::today();
        $state = $this->dailyLogState([$placement])[$placement->id] ?? null;
        if (! $state || ! $state['expected']) {
            return 'not_expected';
        }
        if ($state['logged']) {
            return 'logged';
        }
        $student = $placement->student?->user;
        if (! $student) {
            return 'not_expected';
        }

        // One at a time per student, so two coordinators clicking together send one reminder.
        return Cache::lock('daily-log-remind:'.$placement->id, 10)->get(function () use ($placement, $how, $by, $today, $student) {
            if ($this->nextDailyReminderAt($placement)) {
                return 'waiting';
            }

            $stage = $how === 'auto' ? 'auto' : 'manual:'.now()->format('His');
            $claimed = DB::table('ojt_reminders')->insertOrIgnore([
                'placement_id' => $placement->id,
                'kind' => self::DAILY_LOG,
                'subject_key' => $today->toDateString(),
                'stage' => $stage,
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($claimed === 0) {
                return 'waiting'; // the automatic reminder already went out today
            }

            try {
                $this->notifications->send(
                    $student,
                    'ojt_reminder',
                    'Reminder: log your time today',
                    ($how === 'auto' ? 'You have not logged your time for today' : 'Your coordinator reminded you to log your time for today')
                        .' ('.$today->format('M j').'). Add it on My OJT > Time Record.',
                    ['placement_id' => $placement->id, 'section' => NotificationTarget::TIME_RECORD]
                );
            } catch (\Throwable $exception) {
                DB::table('ojt_reminders')->where(['placement_id' => $placement->id, 'kind' => self::DAILY_LOG, 'subject_key' => $today->toDateString(), 'stage' => $stage])->delete();
                report($exception);

                return 'failed';
            }

            if ($by) {
                $this->activityLog->log($by, 'coordinator_daily_log_remind', ['placement_id' => $placement->id]);
            }

            return 'sent';
        }) ?: 'waiting';
    }

    /** @return array{sent: int, waiting: int} */
    public function remindDailyLogs(?User $coordinator, string $how, ?User $by = null): array
    {
        $result = ['sent' => 0, 'waiting' => 0];
        foreach ($this->notLoggedToday($coordinator) as $placement) {
            $outcome = $this->remindDailyLog($placement, $how, $by);
            if ($outcome === 'sent') {
                $result['sent']++;
            } elseif ($outcome === 'waiting') {
                $result['waiting']++;
            }
        }

        return $result;
    }

    // ------------------------------------------------------------------ weekly / monthly / final

    /**
     * Reminds everyone whose turn it is on the coordinator's ongoing OJTs, as often as it is called.
     *
     * $audience limits it to one group: 'students' or 'supervisors' (null = both).
     *
     * @return array{students: int, supervisors: int}
     */
    public function remindAll(User $coordinator, string $type, ?string $audience = null): array
    {
        [$label, $kind, $section, $overview] = self::TYPES[$type];
        $recipients = []; // user id => ['user' => User, 'student' => bool, 'lines' => list<string>, 'placements' => list<int>]
        $add = function (User $user, bool $isStudent, Placement $placement, string $line) use (&$recipients, $audience) {
            if ($audience !== null && $audience !== ($isStudent ? 'students' : 'supervisors')) {
                return;
            }
            $recipients[$user->id] ??= ['user' => $user, 'student' => $isStudent, 'lines' => [], 'placements' => []];
            $recipients[$user->id]['lines'][] = $line;
            $recipients[$user->id]['placements'][$placement->id] = $placement->id;
        };

        $placements = $this->activePlacements($coordinator)->with(['student.user', 'employer.user', 'supervisorUser.supervisor'])->get();
        foreach ($placements as $placement) {
            $student = $placement->student?->user;
            $name = $student?->name ?? 'An intern';
            foreach ($this->deadlines->pendingFor($placement) as $item) {
                if ($item['kind'] !== $kind) {
                    continue;
                }
                $when = ($item['overdue'] ? 'overdue since ' : 'due ').$item['due_on']->format('M j');
                if ($item['actor'] === 'student' && $student) {
                    $add($student, true, $placement, "{$item['label']} ({$when})");
                } elseif ($item['actor'] === 'supervisor' && ($supervisor = $placement->supervisingUser())) {
                    $add($supervisor, false, $placement, "{$name}: {$item['label']} ({$when})");
                }
            }
            // Weekly reports the student submitted that still wait for the supervisor's certification.
            if ($type === 'weekly') {
                $waiting = $placement->journalEntries()->where('status', JournalEntry::STATUS_SUBMITTED)->count();
                if ($waiting > 0 && ($supervisor = $placement->supervisingUser())) {
                    $add($supervisor, false, $placement, "{$name}: {$waiting} weekly report".($waiting === 1 ? '' : 's').' waiting to be certified');
                }
            }
        }

        $counts = ['students' => 0, 'supervisors' => 0];
        foreach ($recipients as $recipient) {
            $lines = $recipient['lines'];
            $shown = array_slice($lines, 0, 5);
            $more = count($lines) - count($shown);
            $placementIds = array_values($recipient['placements']);
            // One intern: open that intern on the right tab. Several (a supervisor): the sidebar page for this document.
            $data = count($placementIds) === 1
                ? ['placement_id' => $placementIds[0], 'section' => $section]
                : ['overview' => $overview];

            $this->notifications->send(
                $recipient['user'],
                'ojt_reminder',
                'Reminder: '.mb_strtolower($label).($recipient['student'] ? '' : 's to finish'),
                'Your coordinator reminded you: '.implode('; ', $shown).($more > 0 ? "; and {$more} more" : '').'.',
                $data
            );
            $counts[$recipient['student'] ? 'students' : 'supervisors']++;
        }

        $this->activityLog->log($coordinator, 'coordinator_bulk_remind', ['type' => $type, 'audience' => $audience ?? 'both', ...$counts]);

        return $counts;
    }
}
