<?php

namespace App\Models;

use App\Enums\WorkSetup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Internship extends Model
{
    use SoftDeletes;
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
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'work_setup' => WorkSetup::class,
            'allowance' => 'decimal:2',
            'expires_at' => 'datetime',
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
        return $query->where('status', 'open')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isAcceptingApplications(): bool
    {
        return $this->status === 'open'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
