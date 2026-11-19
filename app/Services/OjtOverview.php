<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AttendanceLog;
use App\Models\JournalEntry;
use App\Models\Placement;
use Carbon\CarbonImmutable;

/**
 * The cross-intern pages of a supervisor or the HR account (sidebar: Progress, Time Records, Weekly Reports,
 * Monthly Evaluations, Final Evaluations). For every intern one row: what the section shows for them, and a
 * short list of what still needs to be done or improved. The numbers come from the same places as the
 * intern's own page and from OjtDeadlineService, so the two never disagree.
 */
class OjtOverview
{
    /** key => [title, icon, tab key on the intern's own page, what the page is for] */
    public const SECTIONS = [
        'progress' => ['Progress', 'bi-speedometer2', 'progress', 'How far each intern is, and what is holding them back.'],
        'time-records' => ['Time Records', 'bi-clock-history', 'time-record', 'Each intern\'s daily time records and what is waiting for you or missing.'],
        'weekly-reports' => ['Weekly Reports', 'bi-journal-text', 'weekly-journal', 'Each intern\'s weekly reports: what is not submitted yet or waits to be certified.'],
        'monthly-evaluations' => ['Monthly Evaluations', 'bi-clipboard-data', 'monthly-evaluation', 'Where each intern\'s monthly evaluation stands and whose turn it is.'],
        'final-evaluations' => ['Final Evaluations', 'bi-clipboard-check', 'evaluation', 'Where each intern\'s final evaluation stands.'],
    ];

    private const ACTORS = ['student' => 'student', 'supervisor' => 'supervisor', 'coordinator' => 'coordinator'];

    public function __construct(private OjtDeadlineService $deadlines) {}

    /**
     * @return array{cells: array<string, mixed>, needs: list<array{text: string, tone: string}>}
     */
    public function row(Placement $placement, string $section): array
    {
        return match ($section) {
            'time-records' => $this->timeRecords($placement),
            'weekly-reports' => $this->weeklyReports($placement),
            'monthly-evaluations' => $this->monthlyEvaluations($placement),
            'final-evaluations' => $this->finalEvaluation($placement),
            default => $this->progress($placement),
        };
    }

    private function progress(Placement $placement): array
    {
        $needs = [];
        if ($risk = $placement->riskReason()) {
            $needs[] = $this->need($risk, 'danger');
        }
        $pending = $placement->attendanceLogs->where('status', AttendanceLog::STATUS_PENDING)->count();
        if ($placement->isActive() && $pending > 0) {
            $needs[] = $this->need($pending.' time record'.($pending === 1 ? '' : 's').' waiting for approval', 'warning');
        }
        $overdue = collect($this->deadlines->pendingFor($placement))->where('overdue', true);
        if ($overdue->isNotEmpty()) {
            $needs[] = $this->need($overdue->count().' overdue document'.($overdue->count() === 1 ? '' : 's').': '.$overdue->pluck('label')->take(2)->implode(', ').($overdue->count() > 2 ? ' and more' : ''), 'danger');
        }

        return [
            'cells' => [
                'hours' => $placement->approvedHours(),
                'required' => (int) $placement->required_hours,
                'percent' => $placement->progressPercent(),
                'pending_hours' => $placement->pendingHours(),
                'started' => $placement->start_date,
                'ends' => $placement->end_date,
            ],
            'needs' => $needs,
        ];
    }

    private function timeRecords(Placement $placement): array
    {
        $logs = $placement->attendanceLogs;
        $pending = $logs->where('status', AttendanceLog::STATUS_PENDING);
        $rejected = $logs->where('status', AttendanceLog::STATUS_REJECTED)->count();

        $needs = [];
        if ($placement->isActive()) {
            if ($pending->isNotEmpty()) {
                $needs[] = $this->need($pending->count().' time record'.($pending->count() === 1 ? '' : 's').' waiting for approval ('.round((float) $pending->sum('hours'), 1).' h)', 'warning');
            }
            if ($logs->isEmpty()) {
                $needs[] = $this->need('No time record yet', 'danger');
            } elseif ($risk = $placement->riskReason()) {
                $needs[] = $this->need($risk, 'danger');
            }
            if ($rejected > 0) {
                $needs[] = $this->need($rejected.' rejected time record'.($rejected === 1 ? '' : 's').': the student should correct or re-log '.($rejected === 1 ? 'it' : 'them'), 'info');
            }
        }

        return [
            'cells' => [
                'last' => $logs->max('work_date'),
                'approved_hours' => $placement->approvedHours(),
                'pending' => $pending->count(),
                'rejected' => $rejected,
                'total' => $logs->count(),
            ],
            'needs' => $needs,
        ];
    }

    private function weeklyReports(Placement $placement): array
    {
        $entries = $placement->journalEntries;
        $waiting = $entries->where('status', JournalEntry::STATUS_SUBMITTED)->count();
        $latest = $entries->sortByDesc('week_start')->first();

        $needs = [];
        $missing = collect($this->deadlines->pendingFor($placement))->where('kind', OjtDeadlineService::WEEKLY_REPORT)->values();
        foreach ($missing->take(3) as $item) {
            $needs[] = $this->need($item['label'].' not submitted: '.$this->dueText($item), $item['overdue'] ? 'danger' : 'warning');
        }
        if ($missing->count() > 3) {
            $needs[] = $this->need('and '.($missing->count() - 3).' more weekly report'.($missing->count() - 3 === 1 ? '' : 's').' not submitted', 'danger');
        }
        if ($placement->isActive() && $waiting > 0) {
            $needs[] = $this->need($waiting.' report'.($waiting === 1 ? '' : 's').' waiting to be certified', 'warning');
        }

        return [
            'cells' => [
                'submitted' => $entries->count(),
                'certified' => $entries->where('status', JournalEntry::STATUS_ACKNOWLEDGED)->count(),
                'latest' => $latest,
            ],
            'needs' => $needs,
        ];
    }

    private function monthlyEvaluations(Placement $placement): array
    {
        $appraisals = $placement->appraisals->sortByDesc(fn (Appraisal $a) => [$a->period_month?->timestamp, $a->id])->values();
        $latest = $appraisals->first();

        $needs = [];
        foreach (collect($this->deadlines->pendingFor($placement))->where('kind', OjtDeadlineService::MONTHLY_EVALUATION) as $item) {
            $needs[] = $this->need($item['label'].': waiting for the '.(self::ACTORS[$item['actor']] ?? 'next person').', '.$this->dueText($item), $item['overdue'] ? 'danger' : 'warning');
        }
        if ($placement->isActive() && $appraisals->isEmpty()) {
            $needs[] = $this->need('No monthly evaluation requested yet', 'info');
        }

        return [
            'cells' => [
                'latest' => $latest,
                'complete' => $appraisals->filter(fn (Appraisal $a) => $a->isComplete())->count(),
                'total' => $appraisals->count(),
            ],
            'needs' => $needs,
        ];
    }

    private function finalEvaluation(Placement $placement): array
    {
        $evaluation = $placement->evaluation;
        $needs = [];

        if ($evaluation) {
            if (! $placement->isCleared()) {
                $needs[] = $this->need('Submitted: waiting for the coordinator to review and clear the OJT', 'info');
            }
        } elseif ($placement->isActive()) {
            $pending = collect($this->deadlines->pendingFor($placement))->firstWhere('kind', OjtDeadlineService::FINAL_EVALUATION);
            if ($pending) {
                $needs[] = $this->need('Requested: waiting for the supervisor\'s rating, '.$this->dueText($pending), $pending['overdue'] ? 'danger' : 'warning');
            } elseif ($placement->hasMetRequiredHours()) {
                $needs[] = $this->need('Hours complete: the coordinator can request the final evaluation', 'info');
            } else {
                $needs[] = $this->need(rtrim(rtrim(number_format($placement->remainingHours(), 1), '0'), '.').' h still to render before the final evaluation', 'info');
            }
        }

        return [
            'cells' => [
                'evaluation' => $evaluation,
                'requested_at' => $placement->rating_requested_at,
                'due_on' => $placement->rating_due_on,
                'remaining' => $placement->remainingHours(),
            ],
            'needs' => $needs,
        ];
    }

    /** @return array{text: string, tone: string} */
    private function need(string $text, string $tone): array
    {
        return ['text' => $text, 'tone' => $tone];
    }

    /** "due Nov 20" or "overdue since Nov 20". */
    private function dueText(array $item): string
    {
        /** @var CarbonImmutable $due */
        $due = $item['due_on'];

        return ($item['overdue'] ? 'overdue since ' : 'due ').$due->format('M j');
    }
}
