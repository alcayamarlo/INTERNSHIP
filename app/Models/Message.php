<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'body',
        'read_at',
        'hidden_at',
        'edited_at',
        'unsent_at',
        'sender_removed_at',
        'receiver_removed_at',
        'reply_to_id',
        'pinned_at',
        'pinned_by',
        'forwarded_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'hidden_at' => 'datetime',
            'edited_at' => 'datetime',
            'unsent_at' => 'datetime',
            'sender_removed_at' => 'datetime',
            'receiver_removed_at' => 'datetime',
            'pinned_at' => 'datetime',
            'forwarded_at' => 'datetime',
        ];
    }

    /** Removed by an administrator after a report; shown as a placeholder instead of the text. */
    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    /** Taken back by the sender; the text stays on record but is no longer shown. */
    public function isUnsent(): bool
    {
        return $this->unsent_at !== null;
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    /** What to show in the chat. Pass the viewer's id so the sender sees "You unsent a message". */
    public function displayBody(?int $viewerId = null): string
    {
        if ($this->isHidden()) {
            return 'This message was removed by an administrator.';
        }
        if ($this->isUnsent()) {
            return $viewerId !== null && $viewerId === $this->sender_id ? 'You unsent a message.' : 'This message was unsent.';
        }

        return $this->body;
    }

    /** Messages this user has not removed from their own view ("Remove for you", sent or received). */
    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $sent) => $sent->where('sender_id', $userId)->whereNull('sender_removed_at'))
            ->orWhere(fn (Builder $received) => $received->where('receiver_id', $userId)->whereNull('receiver_removed_at')));
    }

    public function isPinned(): bool
    {
        return $this->pinned_at !== null;
    }

    public function isForwarded(): bool
    {
        return $this->forwarded_at !== null;
    }

    /** Hidden or unsent messages can only be removed or reported, not reacted to, replied to, pinned or forwarded. */
    public function isGone(): bool
    {
        return $this->isHidden() || $this->isUnsent();
    }

    public function isParticipant(int $userId): bool
    {
        return $this->sender_id === $userId || $this->receiver_id === $userId;
    }

    /** The message this one replies to. */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
