<?php

namespace App\Models;

use App\Support\CatalogName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalog of self-declared student skill names (technical, soft, or talents). Flat: no categories.
 */
class SkillTag extends Model
{
    protected $fillable = ['name', 'name_normalized'];

    protected static function booted(): void
    {
        static::saving(function (SkillTag $tag): void {
            $tag->name = CatalogName::display($tag->name);
            $tag->name_normalized = CatalogName::normalized($tag->name);
        });
    }

    public function studentSkills(): HasMany
    {
        return $this->hasMany(StudentSkill::class);
    }
}
