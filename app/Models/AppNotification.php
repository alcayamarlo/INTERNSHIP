<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * In-app notification. Three places it can be:
 *   Inbox    archived_at NULL, deleted_at NULL
 *   Archived archived_at set, deleted_at NULL      (archive / unarchive)
 *   Trash    deleted_at set                        (delete = soft delete; restore or delete forever)
 * Soft-deleted rows are hidden from every normal query by SoftDeletes.
 */
class AppNotification extends Model
{
    use SoftDeletes;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Main inbox: not archived (and not deleted, via SoftDeletes). */
    public function scopeInbox(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /** Unread items that count toward the bell badge (archived ones never do). */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at')->whereNull('archived_at');
    }

    /** $notification->is_read, computed from read_at. */
    protected function isRead(): Attribute
    {
        return Attribute::get(fn (): bool => $this->read_at !== null);
    }

    /** $notification->is_archived, computed from archived_at. */
    protected function isArchived(): Attribute
    {
        return Attribute::get(fn (): bool => $this->archived_at !== null);
    }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function archive(): void
    {
        if (! $this->archived_at) {
            $this->update(['archived_at' => now()]);
        }
    }

    /**
     * Back to the inbox. Named unarchive (it used to be restore()) so it no longer hides
     * SoftDeletes::restore(), which now means "take out of Trash".
     */
    public function unarchive(): void
    {
        if ($this->archived_at) {
            $this->update(['archived_at' => null]);
        }
    }
}
