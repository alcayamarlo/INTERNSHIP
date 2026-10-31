<?php

namespace App\Models;

use App\Enums\WorkSetup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'requires_character_reference',
        'requested_documents',
    ];

    protected function casts(): array
    {
        return [
            'work_setup' => WorkSetup::class,
            'allowance' => 'decimal:2',
            'expires_at' => 'datetime',
            'requires_character_reference' => 'boolean',
            'requested_documents' => 'array',
        ];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /** Programs this internship is open to; none means every program. */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    public function scopeForProgram($query, ?int $programId)
    {
        return $query->where(function ($query) use ($programId) {
            $query->whereDoesntHave('programs');

            if ($programId) {
                $query->orWhereHas('programs', fn ($programs) => $programs->whereKey($programId));
            }
        });
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
