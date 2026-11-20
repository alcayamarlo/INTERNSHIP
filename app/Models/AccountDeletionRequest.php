<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A request to delete (anonymise) a student's account. Approved by an admin, never automatic. */
class AccountDeletionRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_STUDENT = 'student';

    public const SOURCE_RETENTION = 'retention';

    protected $fillable = ['user_id', 'source', 'requested_by_user_id', 'reason', 'status', 'decided_by_user_id', 'decided_at', 'decision_note', 'backup_name', 'summary'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime', 'summary' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
