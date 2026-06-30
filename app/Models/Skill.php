<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    protected $fillable = ['name', 'category', 'description'];

    public function internshipRequirements(): HasMany
    {
        return $this->hasMany(InternshipRequirement::class);
    }
}
