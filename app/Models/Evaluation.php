<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    /**
     * Rubric criteria scored 1 (poor) to 5 (outstanding).
     */
    public const CRITERIA = [
        'quality_of_work' => 'Quality of work',
        'productivity' => 'Productivity and timeliness',
        'technical_skills' => 'Technical / job knowledge',
        'initiative' => 'Initiative and resourcefulness',
        'communication' => 'Communication skills',
        'teamwork' => 'Teamwork and cooperation',
        'professionalism' => 'Professionalism, attendance and punctuality',
    ];

    /** Form 1: the original 1-5 rubric (kept as is). Form 2: the school's Final Performance Evaluation Report. */
    public const FORM_LEGACY = 1;

    public const FORM_SCHOOL = 2;

    /**
     * Part III "Work Performance", copied from the school's form (wording kept in this one place).
     *
     * @var array<string, array{label: string, description: string, points: int}>
     */
    public const WORK_FACTORS = [
        'knowledge' => ['label' => 'Knowledge at work', 'description' => 'able to grasp as instructed', 'points' => 25],
        'quality' => ['label' => 'Quality of Work', 'description' => 'Consider accuracy of work done based on expected output against target date', 'points' => 25],
        'quantity' => ['label' => 'Quantity of Work', 'description' => 'performed as assigned job efficiently as possible', 'points' => 25],
        'punctuality' => ['label' => 'Punctuality', 'description' => 'reports to work assignment on time', 'points' => 10],
        'attendance' => ['label' => 'Attendance', 'description' => 'Follows assigned job efficiently as possible', 'points' => 15],
    ];

    /**
     * Part III "Personality Traits", copied verbatim from the school's form, including its wording.
     *
     * @var array<string, array{label: string, description: string, points: int}>
     */
    public const TRAIT_FACTORS = [
        'physical_appearance' => ['label' => 'Physical Appearance', 'description' => 'Personal growth and always wear appropriate dress.', 'points' => 10],
        'attitude' => ['label' => 'Attitude towards', 'description' => 'Always show enthusiasm and interest.', 'points' => 10],
        'courtesy' => ['label' => 'Courtesy', 'description' => 'Shows respect for authority at all times.', 'points' => 10],
        'conduct' => ['label' => 'Conduct', 'description' => 'Observes rules and regulations.', 'points' => 10],
        'perseverance' => ['label' => 'Perseverance and Industriousness', 'description' => 'Shows initiative and interest at work over and above what is assigned.', 'points' => 10],
        'drive_leadership' => ['label' => 'Drive and Leadership', 'description' => 'Inquisitive and aggressive', 'points' => 10],
        'mental_maturity' => ['label' => 'Mental Maturity', 'description' => 'Effective and calm under pressure', 'points' => 10],
        'sociability' => ['label' => 'Sociability', 'description' => 'Can work harmoniously with another employee.', 'points' => 10],
        'reliability' => ['label' => 'Reliability', 'description' => 'Trusted to use and operate equipment even without further guidance.', 'points' => 10],
        'possession' => ['label' => 'Possession', 'description' => 'The possession of traits for employee in this kind of work.', 'points' => 10],
    ];

    public const SCALE = [
        1 => 'Poor',
        2 => 'Fair',
        3 => 'Satisfactory',
        4 => 'Very satisfactory',
        5 => 'Outstanding',
    ];

    protected $fillable = [
        'placement_id',
        'evaluator_user_id',
        'form_version',
        'header_snapshot',
        'work_scores',
        'work_total',
        'trait_scores',
        'trait_total',
        'department_assigned',
        'field_of_training',
        'supervisor_designation',
        'certified_by_name',
        'certified_by_position',
        'scores',
        'average_score',
        'strengths',
        'improvements',
        'comments',
        'would_hire',
        'submitted_at',
        'coordinator_review_status',
        'coordinator_comment',
        'coordinator_reviewed_at',
        'coordinator_reviewed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'form_version' => 'integer',
            'header_snapshot' => 'encrypted:array', // name, school ID, phone, address (App\Support\SensitiveColumns)
            'work_scores' => 'array',
            'trait_scores' => 'array',
            'work_total' => 'integer',
            'trait_total' => 'integer',
            'average_score' => 'decimal:2',
            'would_hire' => 'boolean',
            'submitted_at' => 'datetime',
            'coordinator_reviewed_at' => 'datetime',
        ];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_user_id');
    }

    /** An evaluation submitted on the original 1-5 rubric; it is shown as is and never rewritten. */
    public function isLegacy(): bool
    {
        return (int) ($this->form_version ?? self::FORM_LEGACY) === self::FORM_LEGACY;
    }

    /**
     * Maps the school form's two percentage totals onto the 1-5 scale used by statistics and lists:
     * ((work + traits) / 2) / 20, e.g. 95% and 100% => 97.5% => 4.88.
     */
    public static function averageFromTotals(int $workTotal, int $traitTotal): float
    {
        return round((($workTotal + $traitTotal) / 2) / 20, 2);
    }

    public function ratingLabel(): string
    {
        return self::SCALE[(int) round((float) $this->average_score)] ?? 'Unrated';
    }
}
