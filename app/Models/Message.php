<?php

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    /** Removed by an administrator after a report; shown as a placeholder instead of the text. */
    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    public function displayBody(): string
    {
        return $this->isHidden() ? 'This message was removed by an administrator.' : $this->body;
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
