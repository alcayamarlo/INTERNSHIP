<?php

namespace App\Models;

use App\Support\CatalogName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A skill the student learned in one certificate. Display-only: never used for the match score.
 */
class StudentCompetencySkill extends Model
{
    public const MAX_PER_CERTIFICATE = 15;

    protected $fillable = ['student_competency_id', 'name', 'name_normalized'];

    protected static function booted(): void
    {
        static::saving(function (StudentCompetencySkill $skill): void {
            $skill->name = CatalogName::display($skill->name);
            $skill->name_normalized = CatalogName::normalized($skill->name);
        });
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(StudentCompetency::class, 'student_competency_id');
    }
}
