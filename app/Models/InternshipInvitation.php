<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternshipInvitation extends Model
{
    protected $fillable = [
        'employer_id',
        'student_id',
        'internship_id',
        'invited_by_user_id',
        'message',
        'status',
        'expires_at',
        'reminded_at',
        'responded_at',
        'application_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'reminded_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Backs the one-pending-invitation-per-student-and-internship unique index.
        static::saving(function (InternshipInvitation $invitation): void {
            $invitation->pending_key = $invitation->status === InvitationStatus::Pending
                ? $invitation->student_id.':'.$invitation->internship_id
                : null;
        });
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(InternshipApplication::class, 'application_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::Pending->value);
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::Pending;
    }

    /** Pending but past its date, or the internship stopped taking applications. */
    public function hasLapsed(): bool
    {
        return $this->expires_at->isPast() || ! $this->internship?->isAcceptingApplications();
    }
}
