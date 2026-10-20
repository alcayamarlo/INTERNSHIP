<?php

use App\Enums\UserRole;
use App\Events\MessageSent;
use App\Events\NotificationCreated;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('broadcasts a NotificationCreated event when a notification is sent, without needing live Pusher credentials', function () {
    Event::fake([NotificationCreated::class]);

    $user = User::factory()->create(['role' => UserRole::Student]);

    $notification = app(NotificationService::class)->send($user, 'test', 'Title', 'Message body');

    Event::assertDispatched(NotificationCreated::class, function (NotificationCreated $event) use ($notification, $user) {
        return $event->notification->id === $notification->id
            && $event->broadcastOn()[0]->name === 'private-App.Models.User.'.$user->id;
    });
});

it('broadcasts a MessageSent event to the recipient when a message is stored', function () {
    Event::fake([MessageSent::class]);

    $sender = User::factory()->create(['role' => UserRole::Employer]);
    $receiver = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $receiver->id]);

    $this->actingAs($sender)
        ->postJson(route('messages.store', $receiver), [
            'body' => 'Hello there',
            'submission_id' => (string) Str::uuid(),
        ])
        ->assertOk();

    Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($sender, $receiver) {
        return $event->message->sender_id === $sender->id
            && $event->message->receiver_id === $receiver->id
            && $event->broadcastOn()[0]->name === 'private-App.Models.User.'.$receiver->id;
    });
});

it('only authorizes a user to subscribe to their own private channel', function () {
    // phpunit.xml forces BROADCAST_CONNECTION=null for tests, and both the
    // null and log drivers no-op auth() entirely (so broadcasting doesn't
    // make real calls during the suite). Only a driver that extends the base
    // Broadcaster (pusher here, with dummy local-only credentials -- no
    // network call happens for channel authorization) actually runs the
    // channel-authorization closure in routes/channels.php, so switch to it
    // and re-register the channel on that driver instance to exercise it.
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => 'test-app-id',
        'broadcasting.connections.pusher.options.cluster' => 'mt1',
    ]);
    require base_path('routes/channels.php');

    $institution = Institution::create(['name' => 'Metro State University', 'address' => '123 Ave', 'contact_email' => 'info@msu.edu.ph']);
    $owner = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $owner->id, 'institution_id' => $institution->id]);
    $other = User::factory()->create(['role' => UserRole::Student]);

    $socketId = '1234.5678';

    $this->actingAs($owner)
        ->post('/broadcasting/auth', ['channel_name' => 'private-App.Models.User.'.$owner->id, 'socket_id' => $socketId])
        ->assertOk();

    $this->actingAs($other)
        ->post('/broadcasting/auth', ['channel_name' => 'private-App.Models.User.'.$owner->id, 'socket_id' => $socketId])
        ->assertForbidden();
});
