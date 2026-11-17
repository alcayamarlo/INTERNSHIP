<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\Coordinator;
use App\Models\Placement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * What the coordinator sees on Reviews > Evaluations: every monthly and final evaluation that was
 * requested for a student of their department, who has done their part, and whose turn it is.
 * Only the coordinator's own department is listed (Coordinator::scopedStudentsQuery()); students
 * and supervisors never see each other's progress, because only coordinator routes use this.
 */
class EvaluationProgress
{
    public const TYPE_MONTHLY = 'monthly';

    public const TYPE_FINAL = 'final';

    public const WAITING_STUDENT = 'waiting_student';

    public const WAITING_SUPERVISOR = 'waiting_supervisor';

    public const WAITING_COORDINATOR = 'waiting_coordinator';

    public const OVERDUE = 'overdue';

    public const COMPLETE = 'complete';

    /** Filter value => label, in the order shown. */
    public const STATUSES = [
        self::WAITING_STUDENT => 'Waiting for student',
        self::WAITING_SUPERVISOR => 'Waiting for supervisor',
        self::WAITING_COORDINATOR => 'Waiting for you',
        self::OVERDUE => 'Overdue',
        self::COMPLETE => 'Complete',
    ];

    public function __construct(private NotificationService $notifications) {}

    public static function today(): string
    {
        return now('Asia/Manila')->toDateString();
    }

    public function monthly(Coordinator $coordinator, ?string $status): LengthAwarePaginator
    {
        return Appraisal::query()
            ->whereHas('placement', fn (Builder $placement) => $this->inScope($placement, $coordinator))
            ->inStage($status)
            ->with(['placement.student.user', 'placement.employer', 'supervisor:id,name'])
            ->orderByRaw('adviser_submitted_at is null desc')
            ->orderBy('due_on')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'page')
            ->withQueryString();
    }

    /** Final evaluations: placements the coordinator requested one for, or that already have one. */
    public function final(Coordinator $coordinator, ?string $status): LengthAwarePaginator
    {
        $query = Placement::query()
            ->tap(fn (Builder $placement) => $this->inScope($placement, $coordinator))
            ->where(fn (Builder $requested) => $requested->whereNotNull('rating_requested_at')->orWhereHas('evaluation'))
            ->with(['student.user', 'employer', 'evaluation.evaluator:id,name']);

        $reviewed = fn (Builder $evaluation) => $evaluation->where('coordinator_review_status', 'reviewed');
        match ($status) {
            self::WAITING_SUPERVISOR => $query->where(fn (Builder $q) => $q->whereDoesntHave('evaluation')
                ->orWhereHas('evaluation', fn (Builder $e) => $e->where('coordinator_review_status', 'returned'))),
            self::WAITING_COORDINATOR => $query->whereHas('evaluation', fn (Builder $e) => $e->whereNull('coordinator_review_status')),
            self::COMPLETE => $query->whereHas('evaluation', $reviewed),
            self::OVERDUE => $query->whereDate('rating_due_on', '<', self::today())->whereDoesntHave('evaluation', $reviewed),
            self::WAITING_STUDENT => $query->whereRaw('1 = 0'), // the supervisor rates the final evaluation alone
            default => null,
        };

        return $query->orderByRaw('rating_due_on is null')->orderBy('rating_due_on')->orderByDesc('id')
            ->paginate(10, ['*'], 'page')->withQueryString();
    }

    /** Number of evaluations (monthly and final) waiting for this coordinator's own rating or review. */
    public function waitingForCoordinator(Coordinator $coordinator): int
    {
        $monthly = Appraisal::query()->whereHas('placement', fn (Builder $p) => $this->inScope($p, $coordinator))->inStage(self::WAITING_COORDINATOR)->count();
        $final = Placement::query()->tap(fn (Builder $p) => $this->inScope($p, $coordinator))
            ->whereHas('evaluation', fn (Builder $e) => $e->whereNull('coordinator_review_status'))->count();

        return $monthly + $final;
    }

    /** The stage of a final evaluation (the monthly one is Appraisal::stage()). */
    public static function finalStage(Placement $placement): string
    {
        $evaluation = $placement->evaluation;

        return match (true) {
            $evaluation?->coordinator_review_status === 'reviewed' => self::COMPLETE,
            $evaluation !== null && $evaluation->coordinator_review_status === null => self::WAITING_COORDINATOR,
            default => self::WAITING_SUPERVISOR, // not rated yet, or returned to the supervisor
        };
    }

    public static function isOverdue(?\Carbon\CarbonInterface $dueOn, bool $complete): bool
    {
        return ! $complete && $dueOn !== null && $dueOn->toDateString() < self::today();
    }

    /**
     * Reminds whoever's turn it is, at most once a day for the same evaluation.
     *
     * @return string|null the name of the person reminded, or null when nobody was (already reminded today,
     *                     or it is the coordinator's own turn)
     */
    public function remind(string $type, int $id, Coordinator $coordinator): ?string
    {
        [$placement, $stage, $title, $label, $data] = $type === self::TYPE_MONTHLY
            ? $this->monthlyTarget($id, $coordinator)
            : $this->finalTarget($id, $coordinator);

        $recipient = match ($stage) {
            self::WAITING_STUDENT => $placement->student?->user,
            self::WAITING_SUPERVISOR => $placement->employer?->user,
            default => null,
        };
        if (! $recipient || ! Cache::add("evaluation-remind:{$type}:{$id}", true, now()->addDay())) {
            return null;
        }

        $this->notifications->send($recipient, 'ojt_update', 'Reminder: '.$title, 'Your coordinator reminded you to finish the '.$label.'.', $data);

        return $recipient->name;
    }

    /** @return array{0: Placement, 1: string, 2: string, 3: string, 4: array} */
    private function monthlyTarget(int $id, Coordinator $coordinator): array
    {
        $appraisal = Appraisal::query()->whereHas('placement', fn (Builder $p) => $this->inScope($p, $coordinator))
            ->with('placement.student.user', 'placement.employer.user')->findOrFail($id);
        $stage = $appraisal->stage();
        $name = $appraisal->period_label.' ('.$appraisal->period_month->format('F Y').')';

        return [
            $appraisal->placement,
            in_array($stage, [Appraisal::STAGE_AWAITING_STUDENT, Appraisal::STAGE_AWAITING_STUDENT_COMMENT], true) ? self::WAITING_STUDENT : ($stage === Appraisal::STAGE_AWAITING_SUPERVISOR ? self::WAITING_SUPERVISOR : self::COMPLETE),
            'monthly evaluation',
            $name.' monthly evaluation',
            ['placement_id' => $appraisal->placement_id, 'appraisal_id' => $appraisal->id],
        ];
    }

    /** @return array{0: Placement, 1: string, 2: string, 3: string, 4: array} */
    private function finalTarget(int $id, Coordinator $coordinator): array
    {
        $placement = Placement::query()->tap(fn (Builder $p) => $this->inScope($p, $coordinator))
            ->with('student.user', 'employer.user', 'evaluation')->findOrFail($id);

        return [
            $placement,
            self::finalStage($placement),
            'final evaluation',
            'final evaluation',
            ['placement_id' => $placement->id, 'section' => NotificationTarget::FINAL_EVALUATION],
        ];
    }

    /** Placements of this coordinator's department only. */
    private function inScope(Builder $placements, Coordinator $coordinator): Builder
    {
        return $placements->whereIn('student_id', $coordinator->scopedStudentsQuery()->select('id'));
    }
}
