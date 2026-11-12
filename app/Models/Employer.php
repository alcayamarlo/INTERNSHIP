<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employer extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'company_name',
        'industry',
        'company_size',
        'description',
        'website',
        'logo',
        'address',
        'contact_person',
        'verified_at',
        'verified_by_user_id',
        'moa_signed_on',
        'moa_expires_on',
        'moa_reminded_at',
        'moa_updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'moa_signed_on' => 'date',
            'moa_expires_on' => 'date',
            'moa_reminded_at' => 'datetime',
        ];
    }

    /** A memorandum of agreement with the school that has not expired. */
    public function hasValidMoa(): bool
    {
        return $this->moa_expires_on !== null && ! $this->moa_expires_on->copy()->endOfDay()->isPast();
    }

    public function moaStatus(): string
    {
        return match (true) {
            $this->moa_expires_on === null => 'No MOA on file',
            ! $this->hasValidMoa() => 'MOA expired',
            $this->moa_expires_on->lte(now()->addDays(30)) => 'MOA expiring soon',
            default => 'MOA valid',
        };
    }

    /** Set by an admin; until then the company can only manage its profile. */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function shortlistedStudents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'employer_shortlists')->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(InternshipInvitation::class);
    }
}
