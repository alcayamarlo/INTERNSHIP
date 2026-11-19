<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * One competency score of a student, 0-100 %. The ten competencies the school rates, in display order, with the
 * range the demo data draws from (database/seeders/Concerns). The scores follow the appraisal factors:
 * Technical Competence, Quality of Work, Quantity of Work, Personality, Initiative, Interpersonal, Punctuality.
 */
class StudentCompetencyScore extends Model
{
    /** skill => [min, max] percent for generated data. The order is the display order. */
    public const SKILLS = [
        'Programming / Technical Competence' => [70, 95],
        'Database Management (SQL)' => [65, 90],
        'Web Development (HTML/CSS/JS/PHP/Laravel)' => [70, 95],
        'Problem Solving / Troubleshooting' => [65, 90],
        'Quality of Work' => [75, 95],
        'Communication Skills' => [70, 95],
        'Teamwork / Interpersonal Skills' => [75, 95],
        'Time Management / Punctuality' => [70, 98],
        'Initiative' => [65, 90],
        'Adaptability / Willingness to Learn' => [75, 98],
    ];

    protected $fillable = ['student_id', 'skill_name', 'score'];

    protected function casts(): array
    {
        return ['score' => 'integer'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * A student's scores for display: the known competencies in their fixed order, then any other.
     *
     * @param  Collection<int, StudentCompetencyScore>  $scores
     * @return list<array{skill: string, score: int}>
     */
    public static function forDisplay(Collection $scores): array
    {
        $order = array_flip(array_keys(self::SKILLS));

        return $scores
            ->sortBy(fn (self $score) => [$order[$score->skill_name] ?? PHP_INT_MAX, $score->skill_name])
            ->map(fn (self $score) => ['skill' => $score->skill_name, 'score' => max(0, min(100, (int) $score->score))])
            ->values()
            ->all();
    }
}
