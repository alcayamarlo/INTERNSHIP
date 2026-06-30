<?php

namespace App\Models;

use App\Enums\CompetencyCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competency extends Model
{
    protected $fillable = [
        'name',
        'category',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'category' => CompetencyCategory::class,
            'is_system' => 'boolean',
        ];
    }

    public function studentCompetencies(): HasMany
    {
        return $this->hasMany(StudentCompetency::class);
    }

    public function internshipRequirements(): HasMany
    {
        return $this->hasMany(InternshipRequirement::class);
    }
}
