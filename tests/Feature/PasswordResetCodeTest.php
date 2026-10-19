<?php

use App\Jobs\SendPasswordResetCode;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Mailer\Exception\TransportException;

uses(RefreshDatabase::class);

function seedPasswordResetCode(User $user, string $code = '123456', ?\Illuminate\Support\Carbon $issuedAt = null): void
{
    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $user->email],
        ['token' => Hash::make($code), 'created_at' => ($issuedAt ?? now())->toDateTimeString()]
    );
    Cache::put('password-reset-attempts:'.hash('sha256', mb_strtolower($user->email)), 0, now()->addMinutes(15));
}

function submitPasswordResetCode(string $email, string $code = '123456'): \Illuminate\Testing\TestResponse
{
    return test()->post(route('password.update'), [
        'email' => $email,
        'code' => $code,
        'password' => 'NewPassword#2026',
        'password_confirmation' => 'NewPassword#2026',
    ]);
}

it('shows only the email field before a reset code is requested', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Enter your email to receive a 6-digit verification code.')
        ->assertSee('Send Reset Code')
        ->assertSee('name="email"', false)
        ->assertDontSee('Six-Digit Code')
        ->assertDontSee('name="code"', false)
        ->assertDontSee('name="password"', false);
});

it('reveals the code and new password fields after requesting a reset code', function () {
    Mail::fake();
    User::factory()->create(['email' => 'reset-step@example.test']);

    $this->post(route('password.email'), ['email' => 'reset-step@example.test'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status')
        ->assertSessionHas('password_reset_code_requested', true);

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Six-Digit Code')
        ->assertSee('New Password')
        ->assertSee('Confirm New Password')
        ->assertSee('Reset Password')
        ->assertSee('Resend Code')
        ->assertSee('data-reset-countdown', false)
        ->assertSee('name="code"', false);

    Mail::assertSent(PasswordResetCodeMail::class, fn (PasswordResetCodeMail $mail) => $mail->hasTo('reset-step@example.test'));
});

it('keeps the reset form on step one when the mail transport fails', function () {
    $user = User::factory()->create(['email' => 'reset-mail-failure@example.test']);
    $pendingMail = Mockery::mock();
    $pendingMail->shouldReceive('send')
        ->once()
        ->andThrow(new TransportException('SMTP unavailable'));
    Mail::shouldReceive('to')
        ->once()
        ->with($user->email)
        ->andReturn($pendingMail);

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors('email')
        ->assertSessionMissing('password_reset_code_requested')
        ->assertSessionMissing('password_reset_code_expires_at');

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Send Reset Code')
        ->assertDontSee('Six-Digit Code')
        ->assertDontSee('New Password')
        ->assertDontSee('Confirm New Password');
});

it('resets the password with a valid code and consumes the code', function () {
    Event::fake([PasswordReset::class]);
    $user = User::factory()->create([
        'email' => 'reset-valid@example.test',
        'password' => 'OldPassword#2026',
        'remember_token' => 'old-remember-token',
    ]);
    seedPasswordResetCode($user);

    submitPasswordResetCode($user->email)
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    expect(Hash::check('NewPassword#2026', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->remember_token)->not->toBe('old-remember-token')
        ->and(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeFalse();

    Event::assertDispatched(PasswordReset::class);
});

it('rejects a wrong code with a generic error and increments the cache attempt counter', function () {
    $user = User::factory()->create(['email' => 'reset-wrong@example.test']);
    seedPasswordResetCode($user);

    submitPasswordResetCode($user->email, '654321')
        ->assertSessionHasErrors('code')
        ->assertSessionHasErrors([
            'code' => 'The reset code is invalid or expired. Request a new code and try again.',
        ]);

    expect(Cache::get('password-reset-attempts:'.hash('sha256', $user->email)))->toBe(1);
});

it('rejects an expired code with the same generic error', function () {
    $user = User::factory()->create(['email' => 'reset-expired@example.test']);
    seedPasswordResetCode($user, '123456', now()->subMinutes(16));

    submitPasswordResetCode($user->email)
        ->assertSessionHasErrors([
            'code' => 'The reset code is invalid or expired. Request a new code and try again.',
        ]);

    expect(Hash::check('NewPassword#2026', $user->fresh()->password))->toBeFalse()
        ->and(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeFalse();
});

it('does not allow a successful reset code to be reused', function () {
    $user = User::factory()->create(['email' => 'reset-reuse@example.test']);
    seedPasswordResetCode($user);

    submitPasswordResetCode($user->email)->assertRedirect(route('login'));
    submitPasswordResetCode($user->email)
        ->assertSessionHasErrors('code');
});

it('invalidates the code after five wrong attempts', function () {
    $user = User::factory()->create(['email' => 'reset-lock@example.test']);
    seedPasswordResetCode($user);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        submitPasswordResetCode($user->email, '654321')->assertSessionHasErrors('code');
    }

    expect(Cache::get('password-reset-attempts:'.hash('sha256', $user->email)))->toBe(5)
        ->and(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeFalse();

    submitPasswordResetCode($user->email)
        ->assertSessionHasErrors('code');
});

it('returns the same response and queues the same job for known and unknown emails', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'reset-known@example.test']);

    $knownResponse = $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email]);
    $unknownResponse = $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'missing@example.test']);

    expect($knownResponse->getSession()->get('status'))
        ->toBe($unknownResponse->getSession()->get('status'))
        ->toBe('If an account exists for that email address, a password reset code is on its way.');

    Mail::assertSent(PasswordResetCodeMail::class, fn (PasswordResetCodeMail $mail) => $mail->hasTo($user->email));
    Mail::assertSent(PasswordResetCodeMail::class, 1);
});

it('queues a reset code that is hashed, expires after fifteen minutes, and resets attempts', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'reset-mail@example.test']);
    Cache::put('password-reset-attempts:'.hash('sha256', $user->email), 4, now()->addMinutes(15));

    (new SendPasswordResetCode($user->email))->handle();

    $reset = DB::table('password_reset_tokens')->where('email', $user->email)->first();

    expect($reset)->not->toBeNull()
        ->and($reset->token)->not->toBe('000000')
        ->and(Cache::get('password-reset-attempts:'.hash('sha256', $user->email)))->toBe(0)
        ->and(now()->diffInMinutes(\Illuminate\Support\Carbon::parse($reset->created_at)))->toBeLessThanOrEqual(1);

    Mail::assertSent(PasswordResetCodeMail::class, fn (PasswordResetCodeMail $mail) => $mail->hasTo($user->email)
        && Hash::check($mail->code, $reset->token)
        && strlen($mail->code) === 6);
});

it('replaces the code and resets attempts when a new code is issued', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'reset-reissue@example.test']);
    $attemptKey = 'password-reset-attempts:'.hash('sha256', $user->email);
    Cache::put($attemptKey, 4, now()->addMinutes(15));

    (new SendPasswordResetCode($user->email))->handle();
    $firstCode = Mail::sent(PasswordResetCodeMail::class)->first()->code;

    Cache::put($attemptKey, 3, now()->addMinutes(15));
    (new SendPasswordResetCode($user->email))->handle();
    $secondMail = Mail::sent(PasswordResetCodeMail::class)->last();
    $storedHash = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');

    expect(Cache::get($attemptKey))->toBe(0)
        ->and(Hash::check($secondMail->code, $storedHash))->toBeTrue()
        ->and(Hash::check($firstCode, $storedHash))->toBeFalse();
});

it('throttles code requests independently by email and ip', function () {
    Bus::fake();
    $email = 'reset-throttle@example.test';

    for ($request = 0; $request < 3; $request++) {
        $this->post(route('password.email'), ['email' => $email])->assertRedirect();
    }

    $this->post(route('password.email'), ['email' => $email])
        ->assertTooManyRequests();

    Bus::assertDispatchedTimes(SendPasswordResetCode::class, 3);
});

it('throttles code submissions per email and per ip', function () {
    $email = 'reset-submit-throttle@example.test';
    $payload = [
        'email' => $email,
        'code' => '123456',
        'password' => 'NewPassword#2026',
        'password_confirmation' => 'NewPassword#2026',
    ];

    for ($submission = 0; $submission < 10; $submission++) {
        $this->post(route('password.update'), $payload)->assertRedirect();
    }

    $this->post(route('password.update'), $payload)->assertTooManyRequests();
});

it('removes the old reset-link route and view path', function () {
    expect(Route::has('password.reset'))->toBeFalse();

    $this->get('/reset-password/legacy-token')->assertNotFound();
});

it('deletes database sessions when resetting a password using the database session driver', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create(['email' => 'reset-sessions@example.test']);
    seedPasswordResetCode($user);
    DB::table('sessions')->insert([
        'id' => 'other-session',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);

    submitPasswordResetCode($user->email)->assertRedirect(route('login'));

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});
