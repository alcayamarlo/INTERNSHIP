<?php

namespace App\Models;

use App\Support\CatalogName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Skill extends Model
{
    protected $fillable = ['name', 'name_normalized', 'category', 'category_id', 'description'];

    protected static function booted(): void
    {
        static::saving(function (Skill $skill): void {
            $skill->name = CatalogName::display($skill->name);
            $skill->name_normalized = CatalogName::normalized($skill->name);

            $category = $skill->category_id
                ? SkillCategory::find($skill->category_id)
                : SkillCategory::where('name_normalized', CatalogName::normalized($skill->category))->first();

            if (! $category) {
                throw (new ModelNotFoundException)->setModel(SkillCategory::class, [$skill->category_id]);
            }

            $skill->category_id = $category->id;
            $skill->category = $category->name;
        });
    }

    public function skillCategory(): BelongsTo
    {
        return $this->belongsTo(SkillCategory::class, 'category_id');
    }

    public function internshipRequirements(): HasMany
    {
        return $this->hasMany(InternshipRequirement::class);
    }
}
