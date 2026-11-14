<?php

namespace App\Services;

use App\Models\AcademicSetting;
use App\Models\Appraisal;
use App\Models\Placement;
use Carbon\CarbonImmutable;

/**
 * What is still owed on an active OJT and when it is due: weekly reports (student), monthly evaluation
 * steps (student, supervisor, student comment), and the final evaluation (supervisor). Used for the
 * "Due" / "Overdue" badges and by ojt:remind-documents. All dates are Philippine time.
 */
class OjtDeadlineService
{
    public const WEEKLY_REPORT = 'weekly_report';

    public const MONTHLY_EVALUATION = 'monthly_evaluation';

    public const FINAL_EVALUATION = 'final_evaluation';

    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now('Asia/Manila')->startOfDay();
    }

    /**
     * Pending items for one placement. Only active placements have any.
     *
     * @return list<array{kind: string, subject_key: string, step: string, actor: string, label: string, due_on: CarbonImmutable, overdue: bool, days_before: int}>
     */
    public function pendingFor(Placement $placement, ?CarbonImmutable $today = null): array
    {
        if (! $placement->isActive()) {
            return [];
        }
        $today ??= self::today();

        return [
            ...$this->weeklyReports($placement, $today),
            ...$this->monthlyEvaluations($placement, $today),
            ...$this->finalEvaluation($placement, $today),
        ];
    }

    /** Weekly report due date: the week's Friday plus the configured number of days. */
    public function weeklyReportDueOn(Placement $placement, int $weekNumber): ?CarbonImmutable
    {
        if (! $placement->start_date) {
            return null;
        }
        $friday = CarbonImmutable::parse($placement->start_date, 'Asia/Manila')->startOfWeek()->addWeeks($weekNumber - 1)->addDays(4)->startOfDay();

        return $friday->addDays((int) (AcademicSetting::current()->journal_due_days ?? 3));
    }

    /** Ended weeks (Mon-Fri) within the OJT that have no report yet. */
    private function weeklyReports(Placement $placement, CarbonImmutable $today): array
    {
        if (! $placement->start_date) {
            return [];
        }
        $from = CarbonImmutable::parse((string) config('ojt_reminders.weekly_reports_from', '2026-11-17'), 'Asia/Manila')->startOfDay();
        $firstMonday = CarbonImmutable::parse($placement->start_date, 'Asia/Manila')->startOfWeek();
        $end = $placement->end_date ? CarbonImmutable::parse($placement->end_date, 'Asia/Manila')->startOfDay() : null;
        $submitted = $placement->journalEntries()->pluck('week_number')->filter()->map(fn ($n) => (int) $n)->all();

        $items = [];
        for ($week = 1, $monday = $firstMonday; ; $week++, $monday = $monday->addWeek()) {
            $friday = $monday->addDays(4);
            if ($friday->gte($today) || ($end && $monday->gt($end))) {
                break; // the week has not ended yet, or is past the OJT end
            }
            if ($friday->lt($from) || in_array($week, $submitted, true)) {
                continue;
            }
            $due = $this->weeklyReportDueOn($placement, $week);
            $items[] = $this->item(self::WEEKLY_REPORT, (string) $week, 'report', 'student', "Week {$week} report", $due, $today,
                (int) config('ojt_reminders.weekly_report_days_before', 1));
        }

        return $items;
    }

    private function monthlyEvaluations(Placement $placement, CarbonImmutable $today): array
    {
        $daysBefore = (int) config('ojt_reminders.evaluation_days_before', 2);

        return $placement->appraisals()->whereNotNull('due_on')->get()
            ->map(function (Appraisal $appraisal) use ($today, $daysBefore) {
                [$step, $actor] = match ($appraisal->stage()) {
                    Appraisal::STAGE_AWAITING_STUDENT => ['student_rating', 'student'],
                    Appraisal::STAGE_AWAITING_SUPERVISOR => ['supervisor_rating', 'supervisor'],
                    Appraisal::STAGE_AWAITING_STUDENT_COMMENT => ['student_comment', 'student'],
                    Appraisal::STAGE_AWAITING_ADVISER => ['adviser_rating', 'coordinator'],
                    default => [null, null],
                };

                return $step === null ? null : $this->item(self::MONTHLY_EVALUATION, (string) $appraisal->id, $step, $actor,
                    "Monthly evaluation ({$appraisal->period_label}, {$appraisal->period_month->format('F Y')})",
                    CarbonImmutable::parse($appraisal->due_on, 'Asia/Manila')->startOfDay(), $today, $daysBefore);
            })
            ->filter()
            ->values()
            ->all();
    }

    private function finalEvaluation(Placement $placement, CarbonImmutable $today): array
    {
        if (! $placement->rating_requested_at || ! $placement->rating_due_on || $placement->evaluation()->exists()) {
            return [];
        }

        return [$this->item(self::FINAL_EVALUATION, 'final', 'supervisor_rating', 'supervisor', 'Final performance evaluation',
            CarbonImmutable::parse($placement->rating_due_on, 'Asia/Manila')->startOfDay(), $today, (int) config('ojt_reminders.evaluation_days_before', 2))];
    }

    private function item(string $kind, string $subjectKey, string $step, string $actor, string $label, CarbonImmutable $dueOn, CarbonImmutable $today, int $daysBefore): array
    {
        return [
            'kind' => $kind,
            'subject_key' => $subjectKey,
            'step' => $step,
            'actor' => $actor,
            'label' => $label,
            'due_on' => $dueOn,
            'overdue' => $today->gt($dueOn),
            'days_before' => $daysBefore,
        ];
    }
}
