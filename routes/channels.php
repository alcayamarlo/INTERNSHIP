<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Every signed-in user gets their own private channel for live notifications
// and messages; only that user may subscribe to it. Messages are pushed here
// only after MessagingEligibility allowed the send, so no per-conversation
// channel (and no second rule set) is needed.
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === $id;
});
