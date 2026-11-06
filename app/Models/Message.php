<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'hidden_at' => 'datetime',
            'edited_at' => 'datetime',
            'unsent_at' => 'datetime',
            'sender_removed_at' => 'datetime',
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

    /** Messages this user has not removed from their own view ("Remove for you"). */
    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('sender_id', '!=', $userId)->orWhereNull('sender_removed_at'));
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
