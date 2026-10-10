<?php

use App\Models\User;
use App\Jobs\SendNotification;
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

    $this->assertDatabaseCount('notifications', 0);
    Queue::assertPushed(SendNotification::class);

    (new SendNotification(
        $user->id,
        'application_update',
        'Application status updated',
        'Your application has been reviewed.',
        ['application_id' => 43]
    ))->handle($service);

    $this->assertDatabaseCount('notifications', 1);
    Mail::assertSent(SystemNotificationMail::class, fn (SystemNotificationMail $mail) => $mail->hasTo($user->email));
});
