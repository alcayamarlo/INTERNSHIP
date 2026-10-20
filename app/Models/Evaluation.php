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
        'scores',
        'average_score',
        'strengths',
        'improvements',
        'comments',
        'would_hire',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'average_score' => 'decimal:2',
            'would_hire' => 'boolean',
            'submitted_at' => 'datetime',
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

    public function ratingLabel(): string
    {
        return self::SCALE[(int) round((float) $this->average_score)] ?? 'Unrated';
    }
}
