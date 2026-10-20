<?php

use App\Models\User;
use App\Models\AppNotification;
use App\Jobs\SendNotification;
use App\Enums\UserRole;
use App\Services\NotificationService;
use App\Mail\SystemNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sends an email when a user notification is created with mail enabled', function () {
    config(['mail.default' => 'smtp']);
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'student@example.com',
        'role' => \App\Enums\UserRole::Student,
    ]);

    app(NotificationService::class)->send(
        $user,
        'application_update',
        'Application status updated',
        'Your application has been reviewed.',
        ['application_id' => 42],
        queue: false
    );

    Mail::assertSent(SystemNotificationMail::class, function (SystemNotificationMail $mail) use ($user) {
        return $mail->hasTo($user->email)
            && $mail->subject === 'Application status updated';
    });
});

it('persists and emails a queued notification exactly once when the job runs', function () {
    config(['mail.default' => 'smtp']);
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create([
        'email' => 'queued-student@example.com',
        'role' => \App\Enums\UserRole::Student,
    ]);

    $service = app(NotificationService::class);
    $service->send(
        $user,
        'application_update',
        'Application status updated',
        'Your application has been reviewed.',
        ['application_id' => 43]
    );

    // The bell entry exists immediately, without waiting for a queue worker.
    $this->assertDatabaseCount('notifications', 1);
    $pushed = null;
    Queue::assertPushed(SendNotification::class, function (SendNotification $job) use (&$pushed) {
        $pushed = $job;

        return $job->notificationId !== null;
    });

    $pushed->handle($service);

    $this->assertDatabaseCount('notifications', 1);
    Mail::assertSent(SystemNotificationMail::class, fn (SystemNotificationMail $mail) => $mail->hasTo($user->email));
});

it('delivers announcements to each selected user role and to all users', function () {
    config([
        'queue.default' => 'sync',
        'mail.default' => 'log',
    ]);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $recipients = [
        UserRole::Student->value => User::factory()->create(['role' => UserRole::Student]),
        UserRole::Coordinator->value => User::factory()->create(['role' => UserRole::Coordinator]),
        UserRole::Employer->value => User::factory()->create(['role' => UserRole::Employer]),
    ];

    foreach ($recipients as $role => $recipient) {
        $title = 'Announcement for '.$role;

        $this->actingAs($admin)
            ->from(route('admin.announcements.index'))
            ->post(route('admin.announcements.store'), [
                'title' => $title,
                'content' => 'This notification is for '.$role.'.',
                'target_role' => $role,
            ])
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipient->id,
            'type' => 'announcement',
            'title' => $title,
        ]);
    }

    $allUsersTitle = 'Announcement for all roles';
    $this->post(route('admin.announcements.store'), [
        'title' => $allUsersTitle,
        'content' => 'This notification is for all roles.',
        'target_role' => 'all',
    ])->assertRedirect(route('admin.announcements.index'));

    foreach ([$admin, ...array_values($recipients)] as $recipient) {
        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipient->id,
            'type' => 'announcement',
            'title' => $allUsersTitle,
        ]);

        $this->actingAs($recipient)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee($allUsersTitle)
            ->assertSee('This notification is for all roles.');
    }

    foreach ($recipients as $role => $recipient) {
        $this->actingAs($recipient)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Announcement for '.$role)
            ->assertSee('This notification is for '.$role.'.');
    }
});

it('allows administrators to edit existing announcements without duplicating notifications', function () {
    config([
        'queue.default' => 'sync',
        'mail.default' => 'log',
    ]);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $recipient = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($admin)
        ->post(route('admin.announcements.store'), [
            'title' => 'Initial notice',
            'content' => 'Initial content.',
            'target_role' => UserRole::Student->value,
        ])->assertRedirect();

    $announcement = \App\Models\Announcement::query()->firstOrFail();
    $this->put(route('admin.announcements.update', $announcement), [
        'title' => 'Updated notice',
        'content' => 'Updated content.',
        'target_role' => 'all',
    ])->assertRedirect();

    expect($announcement->fresh()->title)->toBe('Updated notice')
        ->and($announcement->fresh()->target_role)->toBeNull();
    $this->assertDatabaseCount('notifications', 1);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $recipient->id,
        'title' => 'Initial notice',
    ]);
});

it('requires an announcement audience and rejects administrator as a target', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $announcement = [
        'title' => 'Audience validation',
        'content' => 'Choose an allowed audience.',
    ];

    $this->actingAs($admin)
        ->from(route('admin.announcements.index'))
        ->post(route('admin.announcements.store'), $announcement)
        ->assertRedirect(route('admin.announcements.index'))
        ->assertSessionHasErrors(['target_role']);

    $this->post(route('admin.announcements.store'), [
        ...$announcement,
        'target_role' => UserRole::Admin->value,
    ])->assertRedirect(route('admin.announcements.index'))
        ->assertSessionHasErrors(['target_role']);

    $this->assertDatabaseMissing('announcements', ['title' => 'Audience validation']);
});

it('marks notifications read asynchronously and returns the unread count', function () {
    foreach (UserRole::all() as $role) {
        $user = User::factory()->create(['role' => $role]);
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'type' => 'application_update',
            'title' => 'Application updated',
            'message' => 'Your application was reviewed.',
        ]);

        $this->actingAs($user)
            ->postJson(route('notifications.read', $notification))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }
});

it('opens a notification detail page and marks it read for every role', function () {
    foreach (UserRole::all() as $role) {
        $user = User::factory()->create(['role' => $role]);
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'type' => 'announcement',
            'title' => 'Important update',
            'message' => 'Full notification content for '.$role->value.'.',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('data-notification-open', false)
            ->assertSee(route('notifications.show', $notification), false);

        $this->get(route('notifications.show', $notification))
            ->assertOk()
            ->assertSee('Important update')
            ->assertSee('Full notification content for '.$role->value.'.');

        $this->assertNotNull($notification->fresh()->read_at);
    }
});

it('shows the full published announcement from its notification', function () {
    $user = User::factory()->create(['role' => UserRole::Student]);
    $announcement = \App\Models\Announcement::create([
        'user_id' => User::factory()->create(['role' => UserRole::Admin])->id,
        'title' => 'Long announcement',
        'content' => str_repeat('Full published announcement. ', 8),
        'published_at' => now(),
    ]);
    $notification = AppNotification::create([
        'user_id' => $user->id,
        'type' => 'announcement',
        'title' => $announcement->title,
        'message' => \Illuminate\Support\Str::limit($announcement->content, 120),
        'data' => ['announcement_id' => $announcement->id],
    ]);

    $this->actingAs($user)
        ->get(route('notifications.show', $notification))
        ->assertOk()
        ->assertSee($announcement->content);
});

it('archives read notifications and excludes archived items from unread counts', function () {
    $user = User::factory()->create(['role' => UserRole::Student]);

    $readNotification = AppNotification::create([
        'user_id' => $user->id,
        'type' => 'application_update',
        'title' => 'Read update',
        'message' => 'This should be archived.',
        'read_at' => now(),
    ]);

    $unreadNotification = AppNotification::create([
        'user_id' => $user->id,
        'type' => 'announcement',
        'title' => 'Still active',
        'message' => 'This should remain unread.',
    ]);

    $this->actingAs($user)
        ->postJson(route('notifications.archive', $readNotification))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($readNotification->fresh()->archived_at)->not->toBeNull();
    expect($user->appNotifications()->whereNull('read_at')->whereNull('archived_at')->count())->toBe(1);
    expect($user->appNotifications()->whereNull('read_at')->whereNull('archived_at')->first()->id)->toBe($unreadNotification->id);
});

it('restores archived notifications and can archive all read notifications', function () {
    $user = User::factory()->create(['role' => UserRole::Student]);

    $archived = AppNotification::create([
        'user_id' => $user->id,
        'type' => 'application_update',
        'title' => 'Archived',
        'message' => 'Restore me.',
        'read_at' => now(),
        'archived_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson(route('notifications.restore', $archived))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($archived->fresh()->archived_at)->toBeNull();

    $first = AppNotification::create([
        'user_id' => $user->id,
        'type' => 'message',
        'title' => 'Already read',
        'message' => 'Should be archived.',
        'read_at' => now(),
    ]);

    $second = AppNotification::create([
        'user_id' => $user->id,
        'type' => 'message',
        'title' => 'Unread',
        'message' => 'Should stay active.',
    ]);

    $this->actingAs($user)
        ->postJson(route('notifications.archive-all-read'))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($first->fresh()->archived_at)->not->toBeNull();
    expect($second->fresh()->archived_at)->toBeNull();
    expect($user->appNotifications()->whereNull('read_at')->whereNull('archived_at')->count())->toBe(1);
});
