<?php

namespace App\Models;

use App\Enums\SkillType;
use App\Support\CatalogName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    protected $fillable = ['name', 'name_normalized', 'type', 'type_needs_review', 'description'];

    protected function casts(): array
    {
        return [
            // Fixed Technical/Soft type (Skill Categories were removed).
            'type' => SkillType::class,
            // Set when the old category could not be mapped and Technical was assumed.
            'type_needs_review' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Skill $skill): void {
            $skill->name = CatalogName::display($skill->name);
            $skill->name_normalized = CatalogName::normalized($skill->name);
            $skill->type ??= SkillType::Technical;
        });
    }

    public function internshipRequirements(): HasMany
    {
        return $this->hasMany(InternshipRequirement::class);
    }
}
