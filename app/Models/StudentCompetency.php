<?php

namespace App\Models;

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCompetency extends Model
{
    protected $fillable = [
        'student_id',
        'competency_id',
        'name',
        'category',
        'description',
        'proficiency_level',
        'obtained_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => CompetencyCategory::class,
            'proficiency_level' => ProficiencyLevel::class,
            'obtained_at' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }
}
