<?php

namespace App\Models;

use App\Enums\WorkSetup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Internship extends Model
{
    protected $fillable = [
        'employer_id',
        'title',
        'description',
        'responsibilities',
        'requirements',
        'duration',
        'allowance',
        'work_setup',
        'location',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'work_setup' => WorkSetup::class,
            'allowance' => 'decimal:2',
        ];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function requirementsList(): HasMany
    {
        return $this->hasMany(InternshipRequirement::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(InternshipApplication::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
