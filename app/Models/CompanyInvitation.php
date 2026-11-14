<?php

namespace App\Models;

use App\Enums\CompanyInvitationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A coordinator's invitation to a company with no account yet. "Open" means status = sent and
 * expires_at in the future; the displayed status is derived from expires_at, so a lagging expiry
 * command never shows a stale "Sent".
 */
class CompanyInvitation extends Model
{
    protected $fillable = [
        'reference',
        'invited_by_user_id',
        'company_name',
        'address',
        'email',
        'phone',
        'token_hash',
        'status',
        'expires_at',
        'opened_at',
        'accepted_at',
        'employer_id',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'status' => CompanyInvitationStatus::class,
            'expires_at' => 'datetime',
            'opened_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(string $token): ?self
    {
        // Tokens are 64 characters; anything else cannot match, so skip the query.
        return strlen($token) === 64 ? static::query()->where('token_hash', static::hashToken($token))->first() : null;
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function isOpen(): bool
    {
        return $this->status === CompanyInvitationStatus::Sent && $this->expires_at->isFuture();
    }

    /** The status to show: a sent invitation past its expiry already counts as expired. */
    public function currentStatus(): CompanyInvitationStatus
    {
        return $this->status === CompanyInvitationStatus::Sent && ! $this->expires_at->isFuture()
            ? CompanyInvitationStatus::Expired
            : $this->status;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', CompanyInvitationStatus::Sent->value)->where('expires_at', '>', now());
    }

    /** Filter by the displayed status (see currentStatus()). */
    public function scopeWithCurrentStatus(Builder $query, CompanyInvitationStatus $status): Builder
    {
        return match ($status) {
            CompanyInvitationStatus::Sent => $query->open(),
            CompanyInvitationStatus::Accepted => $query->where('status', CompanyInvitationStatus::Accepted->value),
            CompanyInvitationStatus::Expired => $query->where(fn (Builder $q) => $q
                ->where('status', CompanyInvitationStatus::Expired->value)
                ->orWhere(fn (Builder $sent) => $sent->where('status', CompanyInvitationStatus::Sent->value)->where('expires_at', '<=', now()))),
        };
    }
}
