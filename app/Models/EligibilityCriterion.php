<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One version of a program's internship criteria for a term. Never updated; see EligibilityCriteria. */
class EligibilityCriterion extends Model
{
    protected $table = 'eligibility_criteria';

    protected $fillable = [
        'program_id',
        'school_year',
        'term',
        'version',
        'require_fourth_year',
        'require_major_subjects_passed',
        'required_hours',
        'required_document_ids',
        'notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'require_fourth_year' => 'boolean',
            'require_major_subjects_passed' => 'boolean',
            'required_hours' => 'integer',
            'required_document_ids' => 'array',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeForTerm(Builder $query, int $programId, string $schoolYear, string $term): Builder
    {
        return $query->where('program_id', $programId)->where('school_year', $schoolYear)->where('term', $term);
    }

    /** @return list<int> */
    public function requiredDocumentIds(): array
    {
        return array_values(array_map('intval', $this->required_document_ids ?? []));
    }
}
