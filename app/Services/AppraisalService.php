<?php

namespace App\Services;

use App\Models\AcademicSetting;
use App\Models\Appraisal;
use App\Models\Coordinator;
use App\Models\Placement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Monthly Evaluation workflow. Order (enforced here, not only in the views):
 * coordinator requests a month -> student rates -> supervisor rates and comments -> student comments
 * -> coordinator (adviser) rates. Each step locks the row and re-checks the stage.
 */
class AppraisalService
{
    public function __construct(
        private NotificationService $notifications,
        private ActivityLogService $activityLog,
    ) {}

    /**
     * Months a placement can be evaluated for: from its start month up to its end month, never past the
     * current month.
     *
     * @return Collection<int, CarbonImmutable> first day of each month
     */
    public function monthsFor(Placement $placement): Collection
    {
        if (! $placement->start_date) {
            return collect();
        }

        $first = CarbonImmutable::parse($placement->start_date)->startOfMonth();
        $limit = CarbonImmutable::now()->startOfMonth();
        if ($placement->end_date && CarbonImmutable::parse($placement->end_date)->startOfMonth()->lt($limit)) {
            $limit = CarbonImmutable::parse($placement->end_date)->startOfMonth();
        }

        $months = collect();
        for ($month = $first; $month->lte($limit); $month = $month->addMonth()) {
            $months->push($month);
        }

        return $months;
    }

    /**
     * The month's dates clipped to the placement's start and end (first and last months can be partial).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function defaultPeriod(Placement $placement, CarbonImmutable $month): array
    {
        $start = $month->startOfMonth();
        $end = $month->endOfMonth()->startOfDay();
        $placementStart = CarbonImmutable::parse($placement->start_date)->startOfDay();
        if ($placementStart->gt($start)) {
            $start = $placementStart;
        }
        if ($placement->end_date && CarbonImmutable::parse($placement->end_date)->startOfDay()->lt($end)) {
            $end = CarbonImmutable::parse($placement->end_date)->startOfDay();
        }

        return [$start, $end];
    }

    /** Coordinator requests one month for one placement. */
    public function request(Placement $placement, User $coordinator, CarbonImmutable $month, CarbonImmutable $dueOn, ?CarbonImmutable $start = null, ?CarbonImmutable $end = null): Appraisal
    {
        $month = $month->startOfMonth();
        $this->ensureRequestable($placement, $month);
        [$defaultStart, $defaultEnd] = $this->defaultPeriod($placement, $month);
        $start ??= $defaultStart;
        $end ??= $defaultEnd;

        if ($start->lt($defaultStart) || $end->gt($defaultEnd) || $start->gt($end)) {
            throw ValidationException::withMessages([
                'period_start' => 'Inclusive dates must fall within '.$defaultStart->format('M j').' - '.$defaultEnd->format('M j, Y').' (the month, clipped to the OJT dates).',
            ]);
        }

        $appraisal = DB::transaction(fn () => $placement->appraisals()->create([
            'period_month' => $month->toDateString(),
            'period_label' => AcademicSetting::current()->evaluationPeriodLabelFor($month->month),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'due_on' => $dueOn->toDateString(),
            'requested_by_user_id' => $coordinator->id,
        ]));

        $this->activityLog->log($coordinator, 'appraisal_requested', ['appraisal_id' => $appraisal->id, 'placement_id' => $placement->id]);
        $this->notifyStudent($appraisal, 'Monthly evaluation requested', 'Rate yourself on the '.$this->periodName($appraisal).' monthly evaluation first. Due '.$appraisal->due_on->format('M d, Y').'.');

        return $appraisal;
    }

    /**
     * Requests a month for every active placement the coordinator manages. Placements that already have
     * that month, or whose dates do not cover it, are skipped.
     *
     * @return array{created: int, skipped: int}
     */
    public function bulkRequest(User $coordinator, CarbonImmutable $month, CarbonImmutable $dueOn): array
    {
        $month = $month->startOfMonth();
        $created = 0;
        $skipped = 0;

        foreach ($this->managedActivePlacements($coordinator) as $placement) {
            $covered = $this->monthsFor($placement)->contains(fn (CarbonImmutable $m) => $m->equalTo($month));
            $exists = $placement->appraisals()->whereDate('period_month', $month->toDateString())->exists();
            if (! $covered || $exists) {
                $skipped++;

                continue;
            }
            $this->request($placement, $coordinator, $month, $dueOn);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** @param  array<string, mixed>  $scores */
    public function submitSelf(Appraisal $appraisal, User $student, array $scores): Appraisal
    {
        $appraisal = $this->transition($appraisal, Appraisal::STAGE_AWAITING_STUDENT, function (Appraisal $locked) use ($scores) {
            $clean = self::cleanScores($scores);
            // The trainee details are frozen on the form when the student submits it.
            $info = app(OjtDocumentData::class)->values($locked->placement);
            $locked->update([
                'self_scores' => $clean,
                'self_total' => array_sum($clean),
                'self_submitted_at' => now(),
                'header_snapshot' => collect($info)->only(OjtDocumentData::SNAPSHOT_FIELDS)->all(),
            ]);
        });

        $this->activityLog->log($student, 'appraisal_self_submitted', ['appraisal_id' => $appraisal->id]);
        $this->notifySupervisor($appraisal, 'Monthly evaluation ready to rate', $this->studentName($appraisal).' rated the '.$this->periodName($appraisal).' monthly evaluation. Add your rating and comment.');

        return $appraisal;
    }

    /** @param  array<string, mixed>  $scores */
    public function submitSupervisor(Appraisal $appraisal, User $supervisor, array $scores, string $comment): Appraisal
    {
        $appraisal = $this->transition($appraisal, Appraisal::STAGE_AWAITING_SUPERVISOR, function (Appraisal $locked) use ($scores, $comment, $supervisor) {
            $clean = self::cleanScores($scores);
            $locked->update([
                'supervisor_scores' => $clean,
                'supervisor_total' => array_sum($clean),
                'supervisor_comment' => trim($comment),
                'supervisor_user_id' => $supervisor->id,
                'supervisor_submitted_at' => now(),
            ]);
        });

        $this->activityLog->log($supervisor, 'appraisal_supervisor_submitted', ['appraisal_id' => $appraisal->id]);
        $this->notifyStudent($appraisal, 'Add your comment', 'Your supervisor rated the '.$this->periodName($appraisal).' monthly evaluation. Add your comment to finish your part.');

        return $appraisal;
    }

    public function submitStudentComment(Appraisal $appraisal, User $student, string $comment): Appraisal
    {
        $appraisal = $this->transition($appraisal, Appraisal::STAGE_AWAITING_STUDENT_COMMENT, function (Appraisal $locked) use ($comment) {
            $locked->update(['student_comment' => trim($comment), 'student_comment_submitted_at' => now()]);
        });

        $this->activityLog->log($student, 'appraisal_student_comment_submitted', ['appraisal_id' => $appraisal->id]);
        $this->notifyCoordinators($appraisal, 'Monthly evaluation ready for your rating', $this->studentName($appraisal).'\'s '.$this->periodName($appraisal).' monthly evaluation is ready for the adviser rating.');

        return $appraisal;
    }

    /** @param  array<string, mixed>  $scores */
    public function submitAdviser(Appraisal $appraisal, User $coordinator, array $scores): Appraisal
    {
        $appraisal = $this->transition($appraisal, Appraisal::STAGE_AWAITING_ADVISER, function (Appraisal $locked) use ($scores, $coordinator) {
            $clean = self::cleanScores($scores);
            $locked->update([
                'adviser_scores' => $clean,
                'adviser_total' => array_sum($clean),
                'adviser_user_id' => $coordinator->id,
                'adviser_submitted_at' => now(),
            ]);
        });

        $this->activityLog->log($coordinator, 'appraisal_completed', ['appraisal_id' => $appraisal->id]);
        $this->notifyStudent($appraisal, 'Monthly evaluation complete', 'Your '.$this->periodName($appraisal).' monthly evaluation is complete.');

        return $appraisal;
    }

    /** Reopens only the latest submitted step, one at a time. Returns the reopened step. */
    public function reopen(Appraisal $appraisal, User $coordinator): string
    {
        $step = DB::transaction(function () use ($appraisal) {
            $locked = Appraisal::query()->lockForUpdate()->findOrFail($appraisal->id);
            $step = $locked->latestSubmittedStep();
            if ($step === null) {
                throw ValidationException::withMessages(['appraisal' => 'Nothing has been submitted yet, so there is nothing to reopen.']);
            }

            $locked->update(match ($step) {
                'adviser' => ['adviser_scores' => null, 'adviser_total' => null, 'adviser_user_id' => null, 'adviser_submitted_at' => null],
                'student_comment' => ['student_comment' => null, 'student_comment_submitted_at' => null],
                'supervisor' => ['supervisor_scores' => null, 'supervisor_total' => null, 'supervisor_comment' => null, 'supervisor_user_id' => null, 'supervisor_submitted_at' => null],
                'self' => ['self_scores' => null, 'self_total' => null, 'self_submitted_at' => null],
            });

            return $step;
        });

        $appraisal->refresh();
        // The reopened step can be reminded again (ojt:remind-documents).
        $reminderStep = match ($step) {
            'self' => 'student_rating',
            'supervisor' => 'supervisor_rating',
            'student_comment' => 'student_comment',
            default => 'adviser_rating',
        };
        DB::table('ojt_reminders')
            ->where('placement_id', $appraisal->placement_id)
            ->where('kind', OjtDeadlineService::MONTHLY_EVALUATION)
            ->where('subject_key', (string) $appraisal->id)
            ->where('stage', 'like', $reminderStep.':%')
            ->delete();
        $this->activityLog->log($coordinator, 'appraisal_reopened', ['appraisal_id' => $appraisal->id, 'step' => $step]);
        $message = 'Your coordinator reopened the '.Appraisal::stepLabel($step).' on the '.$this->periodName($appraisal).' monthly evaluation. Please submit it again.';
        match ($step) {
            'supervisor' => $this->notifySupervisor($appraisal, 'Monthly evaluation reopened', $message),
            'adviser' => null,
            default => $this->notifyStudent($appraisal, 'Monthly evaluation reopened', $message),
        };

        return $step;
    }

    /**
     * Integer scores from 0 to each factor's points, for every factor. Callers validate the request first;
     * this is the final guard so a bad value can never be stored.
     *
     * @param  array<string, mixed>  $scores
     * @return array<string, int>
     */
    public static function cleanScores(array $scores): array
    {
        $clean = [];
        foreach (Appraisal::FACTORS as $key => $factor) {
            $value = $scores[$key] ?? null;
            if (! is_numeric($value) || (int) $value != $value || (int) $value < 0 || (int) $value > $factor['points']) {
                throw ValidationException::withMessages(["scores.{$key}" => "Enter a whole number from 0 to {$factor['points']} for {$factor['label']}."]);
            }
            $clean[$key] = (int) $value;
        }

        return $clean;
    }

    /** Validation rules for a score set, shared by the three rating forms. */
    public static function scoreRules(): array
    {
        $rules = [];
        foreach (Appraisal::FACTORS as $key => $factor) {
            $rules['scores.'.$key] = ['required', 'integer', 'between:0,'.$factor['points']];
        }

        return $rules;
    }

    public static function scoreMessages(): array
    {
        $messages = [];
        foreach (Appraisal::FACTORS as $key => $factor) {
            $messages['scores.'.$key.'.required'] = "Rate {$factor['label']}.";
            $messages['scores.'.$key.'.integer'] = "Enter a whole number for {$factor['label']}.";
            $messages['scores.'.$key.'.between'] = "{$factor['label']} must be from 0 to {$factor['points']}.";
        }

        return $messages;
    }

    /** @return Collection<int, Placement> */
    public function managedActivePlacements(User $coordinator): Collection
    {
        $profile = $coordinator->coordinator;
        if (! $profile) {
            return collect();
        }

        return Placement::query()
            ->where('status', Placement::STATUS_ACTIVE)
            ->whereIn('student_id', $profile->scopedStudentsQuery()->select('id'))
            ->with('student')
            ->get()
            ->filter(fn (Placement $placement) => $placement->student && $profile->canAccessStudent($placement->student))
            ->values();
    }

    private function ensureRequestable(Placement $placement, CarbonImmutable $month): void
    {
        if (! $placement->isActive()) {
            throw ValidationException::withMessages(['period_month' => 'Monthly evaluations can only be requested while the OJT is active.']);
        }
        if (! $this->monthsFor($placement)->contains(fn (CarbonImmutable $m) => $m->equalTo($month))) {
            throw ValidationException::withMessages(['period_month' => 'Choose a month within the OJT dates, up to the current month.']);
        }
        if ($placement->appraisals()->whereDate('period_month', $month->toDateString())->exists()) {
            throw ValidationException::withMessages(['period_month' => 'This month already has a monthly evaluation.']);
        }
    }

    /** Locks the row, checks the stage, applies the change. */
    private function transition(Appraisal $appraisal, string $expectedStage, \Closure $apply): Appraisal
    {
        return DB::transaction(function () use ($appraisal, $expectedStage, $apply) {
            $locked = Appraisal::query()->lockForUpdate()->findOrFail($appraisal->id);
            if ($locked->stage() !== $expectedStage) {
                throw ValidationException::withMessages(['appraisal' => 'This step is not open right now. '.$locked->stageLabel().'.']);
            }
            $apply($locked);

            return $locked->fresh();
        });
    }

    private function periodName(Appraisal $appraisal): string
    {
        return $appraisal->period_label.' ('.$appraisal->period_month->format('F Y').')';
    }

    private function studentName(Appraisal $appraisal): string
    {
        $appraisal->loadMissing('placement.student.user');

        return $appraisal->placement?->student?->user?->name ?? 'The student';
    }

    private function notifyStudent(Appraisal $appraisal, string $title, string $message): void
    {
        $appraisal->loadMissing('placement.student.user');
        if ($user = $appraisal->placement?->student?->user) {
            $this->notifications->send($user, 'ojt_update', $title, $message, ['placement_id' => $appraisal->placement_id, 'appraisal_id' => $appraisal->id]);
        }
    }

    private function notifySupervisor(Appraisal $appraisal, string $title, string $message): void
    {
        $appraisal->loadMissing('placement.employer.user');
        if ($user = $appraisal->placement?->supervisingUser()) {
            $this->notifications->send($user, 'ojt_update', $title, $message, ['placement_id' => $appraisal->placement_id, 'appraisal_id' => $appraisal->id]);
        }
    }

    private function notifyCoordinators(Appraisal $appraisal, string $title, string $message): void
    {
        $appraisal->loadMissing('placement.student');
        $student = $appraisal->placement?->student;
        if (! $student?->institution_id) {
            return;
        }

        Coordinator::query()
            ->where('institution_id', $student->institution_id)
            ->with('user')
            ->get()
            ->filter(fn (Coordinator $coordinator) => $coordinator->user && $coordinator->canAccessStudent($student))
            ->each(fn (Coordinator $coordinator) => $this->notifications->send(
                $coordinator->user,
                'ojt_update',
                $title,
                $message,
                ['placement_id' => $appraisal->placement_id, 'appraisal_id' => $appraisal->id, 'student_id' => $student->id]
            ));
    }
}
