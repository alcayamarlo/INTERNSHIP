<?php

use App\Enums\UserRole;
use App\Mail\EmailVerificationCodeMail;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('redirects unverified users away from verified routes', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('verification.notice'));

    $this->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Enter the 6-digit code sent to your email address.');
});

it('allows verified users to open their protected dashboard', function () {
    $user = User::factory()->create(['role' => UserRole::Student]);
    \App\Models\Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertOk();
});

it('verifies an email with its current six-digit code', function () {
    $user = User::factory()->unverified()->create([
        'verification_code' => Hash::make('004281'),
        'verification_code_expires_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($user)
        ->post(route('verification.verify'), ['code' => '004281'])
        ->assertRedirect(route('student.dashboard'));

    $user->refresh();

    expect($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->verification_code)->toBeNull()
        ->and($user->verification_code_expires_at)->toBeNull()
        ->and($user->verification_attempts)->toBe(0);
});

it('rejects an incorrect verification code and counts the attempt', function () {
    $user = User::factory()->unverified()->create([
        'verification_code' => Hash::make('123456'),
        'verification_code_expires_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.verify'), ['code' => '654321'])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHasErrors('code');

    expect($user->fresh()->verification_attempts)->toBe(1);
});

it('requires exactly six numeric digits without consuming an attempt', function () {
    $user = User::factory()->unverified()->create([
        'verification_code' => Hash::make('123456'),
        'verification_code_expires_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($user)
        ->post(route('verification.verify'), ['code' => '12ab'])
        ->assertSessionHasErrors('code');

    expect($user->fresh()->verification_attempts)->toBe(0);
});

it('rejects an expired verification code', function () {
    $user = User::factory()->unverified()->create([
        'verification_code' => Hash::make('123456'),
        'verification_code_expires_at' => now()->subSecond(),
    ]);

    $this->actingAs($user)
        ->post(route('verification.verify'), ['code' => '123456'])
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('locks verification after five incorrect attempts', function () {
    $user = User::factory()->unverified()->create([
        'verification_code' => Hash::make('123456'),
        'verification_code_expires_at' => now()->addMinutes(10),
    ]);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->actingAs($user)
            ->post(route('verification.verify'), ['code' => '654321'])
            ->assertSessionHasErrors('code');
    }

    expect($user->fresh()->verification_attempts)->toBe(5);

    $this->actingAs($user)
        ->post(route('verification.verify'), ['code' => '123456'])
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('replaces the previous verification code when a new one is requested', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create([
        'verification_code' => Hash::make('123456'),
        'verification_code_expires_at' => now()->addMinutes(10),
        'verification_attempts' => 3,
    ]);

    $this->actingAs($user)
        ->post(route('verification.resend'))
        ->assertRedirect()
        ->assertSessionHas('status');

    Mail::assertSent(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use ($user) {
        $freshUser = $user->fresh();

        return $mail->hasTo($user->email)
            && Hash::check($mail->code, $freshUser->verification_code)
            && ! Hash::check('123456', $freshUser->verification_code)
            && $freshUser->verification_code_expires_at->isFuture()
            && $freshUser->verification_attempts === 0;
    });
});

it('throttles verification code resends', function () {
    Mail::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.resend'))
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->post(route('verification.resend'))
        ->assertTooManyRequests();

    Mail::assertSent(EmailVerificationCodeMail::class, 1);
});

it('emails a verification code when a user registers', function () {
    Http::fake();
    Mail::fake();

    $institution = Institution::create([
        'name' => "St. Cecilia's College-Cebu, Inc.",
        'address' => null,
    ]);

    $this->post(route('register.store'), [
        'name' => 'New Student',
        'email' => 'new-student@example.com',
        'password' => 'DistinctivePassword#2026',
        'password_confirmation' => 'DistinctivePassword#2026',
        'institution_id' => $institution->id,
        'program' => 'Bachelor of Science in Information Technology',
    ])->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'new-student@example.com')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->verification_attempts)->toBe(0)
        ->and($user->verification_code_expires_at->isFuture())->toBeTrue();

    Mail::assertSent(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use ($user) {
        return $mail->hasTo($user->email)
            && Hash::check($mail->code, $user->verification_code);
    });
});
