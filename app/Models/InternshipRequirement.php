<?php

namespace App\Models;

use App\Enums\ProficiencyLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternshipRequirement extends Model
{
    protected $fillable = [
        'internship_id',
        'competency_id',
        'skill_id',
        'requirement_name',
        'required_level',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'required_level' => ProficiencyLevel::class,
            'is_required' => 'boolean',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
