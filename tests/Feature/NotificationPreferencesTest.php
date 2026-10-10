<?php

use App\Enums\UserRole;
use App\Mail\SystemNotificationMail;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('does not send emails when email notifications are disabled', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'student@example.com',
        'role' => UserRole::Student,
        'email_notifications' => false,
    ]);

    app(NotificationService::class)->send(
        $user,
        'application_update',
        'Application status updated',
        'Your application has been reviewed.',
        ['application_id' => 42],
        queue: false
    );

    Mail::assertNothingSent();
});
