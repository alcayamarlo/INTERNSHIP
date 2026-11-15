<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Monthly Evaluation: the school's "Performance Appraisal Report – OJT / Practicum Trainee".
 * One per placement per month. Each block is locked once submitted; the coordinator may reopen
 * only the latest submitted step.
 */
class Appraisal extends Model
{
    public const STAGE_AWAITING_STUDENT = 'awaiting_student';

    public const STAGE_AWAITING_SUPERVISOR = 'awaiting_supervisor';

    public const STAGE_AWAITING_STUDENT_COMMENT = 'awaiting_student_comment';

    public const STAGE_AWAITING_ADVISER = 'awaiting_adviser';

    public const STAGE_COMPLETE = 'complete';

    /**
     * The form's factors, copied from the school's sample. Wording lives only here so a correction is
     * a one-place change. Points are the maximum percentage for each factor (total 100).
     *
     * @var array<string, array{label: string, description: string, points: int}>
     */
    public const FACTORS = [
        'technical_competence' => ['label' => 'Technical Competence', 'description' => 'Applies technical knowledge and ability to the job.', 'points' => 30],
        'quality_of_work' => ['label' => 'Quality of Work', 'description' => 'Consistently produces high-quality results through thoughtful application and effort.', 'points' => 15],
        'quantity_of_work' => ['label' => 'Quantity of Work', 'description' => 'Consistently achieves objectives and meets established standards for the quality of work produced.', 'points' => 15],
        'personality' => ['label' => 'Personality', 'description' => 'Is cheerful, outgoing, with good communication skills, well groomed.', 'points' => 10],
        'initiative' => ['label' => 'Initiative', 'description' => 'With exceptional ability to do things without being told/seeks additional work.', 'points' => 10],
        'interpersonal' => ['label' => 'Interpersonal Relationship / Skills', 'description' => 'Demonstrates good communication and collaboration, building positive relationships with others.', 'points' => 10],
        'punctuality_attendance' => ['label' => 'Punctuality / Attendance', 'description' => 'Consistently arrives on time and maintains excellent attendance.', 'points' => 10],
    ];

    public const INSTRUCTION = 'Please indicate the percentage rating for each category. The student should provide their rating first, followed by the supervisor\'s rating. In the remarks section, the trainee should enter their comments immediately after the supervisor\'s comments. This appraisal report is confidential and must not be shared with any other OJT/Practicum trainee.';

    /** Default names for the evaluation period by month (configurable in Academic settings). */
    public const DEFAULT_PERIOD_LABELS = [1 => 'Prelim', 2 => 'Midterm', 3 => 'Prefinals', 4 => 'Finals'];

    protected $fillable = [
        'placement_id',
        'period_month',
        'period_label',
        'period_start',
        'period_end',
        'header_snapshot',
        'due_on',
        'requested_by_user_id',
        'self_scores',
        'self_total',
        'self_submitted_at',
        'supervisor_scores',
        'supervisor_total',
        'supervisor_comment',
        'supervisor_user_id',
        'supervisor_submitted_at',
        'student_comment',
        'student_comment_submitted_at',
        'adviser_scores',
        'adviser_total',
        'adviser_user_id',
        'adviser_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'header_snapshot' => 'array',
            'due_on' => 'date',
            'self_scores' => 'array',
            'supervisor_scores' => 'array',
            'adviser_scores' => 'array',
            'self_submitted_at' => 'datetime',
            'supervisor_submitted_at' => 'datetime',
            'student_comment_submitted_at' => 'datetime',
            'adviser_submitted_at' => 'datetime',
        ];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_user_id');
    }

    public function stage(): string
    {
        return match (true) {
            $this->self_submitted_at === null => self::STAGE_AWAITING_STUDENT,
            $this->supervisor_submitted_at === null => self::STAGE_AWAITING_SUPERVISOR,
            $this->student_comment_submitted_at === null => self::STAGE_AWAITING_STUDENT_COMMENT,
            $this->adviser_submitted_at === null => self::STAGE_AWAITING_ADVISER,
            default => self::STAGE_COMPLETE,
        };
    }

    public function stageLabel(): string
    {
        return match ($this->stage()) {
            self::STAGE_AWAITING_STUDENT => 'Waiting for the student\'s rating',
            self::STAGE_AWAITING_SUPERVISOR => 'Waiting for the supervisor\'s rating',
            self::STAGE_AWAITING_STUDENT_COMMENT => 'Waiting for the student\'s comment',
            self::STAGE_AWAITING_ADVISER => 'Waiting for the coordinator\'s rating',
            default => 'Complete',
        };
    }

    public function isComplete(): bool
    {
        return $this->stage() === self::STAGE_COMPLETE;
    }

    /** The submitted step the coordinator may reopen, or null when nothing is submitted. */
    public function latestSubmittedStep(): ?string
    {
        return match (true) {
            $this->adviser_submitted_at !== null => 'adviser',
            $this->student_comment_submitted_at !== null => 'student_comment',
            $this->supervisor_submitted_at !== null => 'supervisor',
            $this->self_submitted_at !== null => 'self',
            default => null,
        };
    }

    public static function stepLabel(string $step): string
    {
        return match ($step) {
            'self' => 'student rating',
            'supervisor' => 'supervisor rating',
            'student_comment' => 'student comment',
            'adviser' => 'coordinator rating',
            default => $step,
        };
    }

    /** "February 1 - 28, 2026", or across months when clipped dates require it. */
    public function inclusiveDates(): string
    {
        $start = $this->period_start;
        $end = $this->period_end;

        return $start->isSameMonth($end)
            ? $start->format('F j').' - '.$end->format('j, Y')
            : $start->format('F j').' - '.$end->format('F j, Y');
    }

    public static function maxTotal(): int
    {
        return array_sum(array_column(self::FACTORS, 'points'));
    }
}
