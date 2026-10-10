<?php

namespace App\Models;

use App\Support\CatalogName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillCategory extends Model
{
    protected $fillable = ['name', 'name_normalized'];

    protected static function booted(): void
    {
        static::saving(function (SkillCategory $category): void {
            $category->name = CatalogName::display($category->name);
            $category->name_normalized = CatalogName::normalized($category->name);
        });
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class, 'category_id');
    }
}
