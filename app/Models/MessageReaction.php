<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One person's emoji reaction to a chat message (one per person per message). */
class MessageReaction extends Model
{
    /** The reactions offered in the chat, in picker order. */
    public const EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '😡'];

    protected $fillable = ['message_id', 'user_id', 'emoji'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
