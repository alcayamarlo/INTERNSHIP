<?php

use App\Enums\UserRole;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

const TF_PASSWORD = 'Correct-Horse-9';

/** A ready account (verified email, first password already set) with a real-looking address. */
function tfUser(UserRole $role = UserRole::Admin, array $attributes = []): User
{
    static $n = 0;
    $n++;

    // Attributes passed in win over these defaults.
    return User::factory()->create($attributes + ['role' => $role, 'email' => "person{$n}@scc.edu.ph", 'password' => TF_PASSWORD, 'email_verified_at' => now(), 'must_change_password' => false]);
}

function tfSignIn(User $user): \Illuminate\Testing\TestResponse
{
    return test()->post(route('login'), ['login' => $user->email, 'password' => TF_PASSWORD]);
}

function tfLastCode(): string
{
    return Mail::sent(TwoFactorCodeMail::class)->last()->code;
}

/* ---------- The second step ---------- */

it('asks an admin for the emailed code after the password, and signs in with it once', function () {
    Mail::fake();
    $admin = tfUser();

    tfSignIn($admin)->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
    Mail::assertSent(TwoFactorCodeMail::class, fn ($mail) => $mail->hasTo($admin->email));
    $this->get(route('two-factor.challenge'))->assertOk()
        ->assertSee('data-two-factor-state="sent"', false)->assertSee(TwoFactorChallengeController::mask($admin->email));

    // Stored only as a keyed hash.
    $code = tfLastCode();
    expect($code)->toMatch('/^\d{6}$/')
        ->and(DB::table('users')->where('id', $admin->id)->value('two_factor_code_hash'))->toHaveLength(64)->not->toContain($code);

    $this->post(route('two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
    $this->assertGuest();

    $this->post(route('two-factor.verify'), ['code' => substr($code, 0, 3).' '.substr($code, 3)])->assertRedirect(route('account.security'));
    $this->assertAuthenticatedAs($admin);
    $this->assertDatabaseHas('system_logs', ['user_id' => $admin->id, 'action' => 'two_factor_passed']);

    // A code works once.
    $this->post(route('logout'));
    tfSignIn($admin);
    $this->post(route('two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('cancels a code after 5 wrong tries, after 10 minutes, and gives a new one on request', function () {
    Mail::fake();
    $admin = tfUser();
    tfSignIn($admin);
    $first = tfLastCode();

    foreach (range(1, 5) as $try) {
        $this->post(route('two-factor.verify'), ['code' => '111111'])->assertSessionHasErrors('code');
    }
    $this->post(route('two-factor.verify'), ['code' => $first])->assertSessionHasErrors('code'); // too late: cancelled
    $this->assertGuest();

    $this->post(route('two-factor.resend'))->assertRedirect(route('two-factor.challenge'));
    $this->post(route('two-factor.verify'), ['code' => tfLastCode()])->assertRedirect(); // a new code works
    $this->assertAuthenticatedAs($admin);
});

it('rejects an expired code and an expired sign-in', function () {
    Mail::fake();
    $admin = tfUser();
    tfSignIn($admin);
    $code = tfLastCode();

    $this->travel(11)->minutes();
    $this->post(route('two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code'); // code expired (10 min)
    $this->post(route('two-factor.resend'))->assertRedirect(route('two-factor.challenge'));
    $fresh = tfLastCode();

    $this->travel(16)->minutes(); // the sign-in itself is older than 15 minutes
    $this->post(route('two-factor.verify'), ['code' => $fresh])->assertRedirect(route('login'));
    $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('stops after too many wrong codes in an hour', function () {
    Mail::fake();
    $admin = tfUser();
    $service = app(TwoFactor::class);
    foreach (range(1, 20) as $try) {
        expect($service->verifyCode($admin, '999999'))->toBeFalse();
    }

    tfSignIn($admin);
    $this->post(route('two-factor.verify'), ['code' => tfLastCode()])->assertSessionHasErrors('code');
    $this->assertGuest();
    expect(session('errors')->first('code'))->toContain('Too many wrong codes');
});

it('does not ask students, ordinary staff, or accounts that are not set up yet', function () {
    Mail::fake();
    foreach ([tfUser(UserRole::Student), tfUser(UserRole::Coordinator), tfUser(UserRole::Employer), tfUser(UserRole::Supervisor)] as $user) {
        tfSignIn($user)->assertRedirect(route($user->dashboardRoute()));
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'));
    }
    // A student cannot be forced into it, even by the flag; an admin who has not verified an email or set a password yet signs in to finish setup.
    tfSignIn(tfUser(UserRole::Student, ['two_factor_enabled' => true]))->assertRedirect();
    $this->assertAuthenticated();
    $this->post(route('logout'));
    tfSignIn(tfUser(UserRole::Admin, ['must_change_password' => true]))->assertRedirect(route('password.force-change'));
    $this->post(route('logout'));
    tfSignIn(tfUser(UserRole::Admin, ['email_verified_at' => null]))->assertRedirect(route('verification.notice'));
    Mail::assertNothingSent();
});

it('asks a coordinator, HR or supervisor only after they switch it on', function () {
    Mail::fake();
    foreach ([UserRole::Coordinator, UserRole::Employer, UserRole::Supervisor] as $role) {
        $user = tfUser($role);
        $this->actingAs($user)->post(route('account.security.enable'), ['password' => TF_PASSWORD])->assertRedirect(route('account.security'));
        expect($user->fresh()->two_factor_enabled)->toBeTrue();
        $this->post(route('logout'));

        tfSignIn($user)->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->post(route('two-factor.verify'), ['code' => tfLastCode()])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'));
    }
});

/* ---------- Where it does not apply or send ---------- */

it('skips the code step on a development machine (APP_ENV=local) and sends nothing', function () {
    Mail::fake();
    // CSRF checks are only skipped in the testing environment; this test pretends to be local.
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    app()->detectEnvironment(fn () => 'local');
    $admin = tfUser();

    tfSignIn($admin)->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin);
    Mail::assertNothingSent();
    expect(app(TwoFactor::class)->skippedHere())->toBeTrue();

    config(['security.two_factor.skip_in_local' => false]); // the real flow can be tried locally
    $this->post(route('logout'));
    tfSignIn($admin)->assertRedirect(route('two-factor.challenge'));
});

it('never emails a code to demo accounts or addresses that are not real mailboxes', function () {
    Mail::fake();
    $service = app(TwoFactor::class);

    $demo = tfUser(UserRole::Coordinator, ['email' => 'lorna.estenzo@gmail.com', 'demo_account' => true, 'two_factor_enabled' => true]);
    $reserved = [
        tfUser(UserRole::Admin, ['email' => 'admin@skillbridge.test']),
        tfUser(UserRole::Admin, ['email' => 'someone@example.com']),
        tfUser(UserRole::Admin, ['email' => 'someone@mail.example']),
    ];
    foreach ([$demo, ...$reserved] as $user) {
        expect($service->canEmail($user))->toBeFalse($user->email.' must not get codes');
        expect($service->sendCode($user))->toBe('unavailable');
    }
    expect($service->canEmail(tfUser(UserRole::Admin, ['email' => 'registrar@gmail.com'])))->toBeTrue()
        ->and($service->canEmail(tfUser(UserRole::Admin, ['email_verified_at' => null])))->toBeFalse();

    // Signing in as one of them: the page says so, nothing is sent, a demo account cannot switch it on.
    tfSignIn($demo)->assertRedirect(route('two-factor.challenge'));
    $this->get(route('two-factor.challenge'))->assertSee('data-two-factor-state="unavailable"', false)->assertDontSee('Send a new code');
    Mail::assertNothingSent();

    $this->post(route('logout'));
    $plain = tfUser(UserRole::Coordinator, ['email' => 'x.y@gmail.com', 'demo_account' => true]);
    $this->actingAs($plain)->post(route('account.security.enable'), ['password' => TF_PASSWORD])->assertSessionHasErrors('password');
    expect($plain->fresh()->two_factor_enabled)->toBeFalse();
});

/* ---------- Recovery codes ---------- */

it('shows recovery codes once with download and print, and stores only hashes', function () {
    $admin = tfUser();
    $this->actingAs($admin)->post(route('account.security.recovery-codes'), ['password' => 'wrong'])->assertSessionHasErrors('password');
    expect($admin->fresh()->two_factor_recovery_codes)->toBeNull();

    $this->post(route('account.security.recovery-codes'), ['password' => TF_PASSWORD])->assertRedirect(route('account.security'));
    $html = $this->get(route('account.security'))->assertOk()
        ->assertSee('data-recovery-codes', false)->assertSee('data-recovery-download', false)->assertSee('data-recovery-print', false)->getContent();
    preg_match_all('#<code>([a-z0-9]{5}-[a-z0-9]{5})</code>#', $html, $m);
    expect($m[1])->toHaveCount(8)->and(array_unique($m[1]))->toHaveCount(8);

    // The download is a text file holding the codes.
    preg_match('#data:text/plain;charset=utf-8,([^"]+)"#', $html, $download);
    expect(urldecode($download[1]))->toContain($m[1][0])->toContain($m[1][7]);

    // Gone on the next visit; the database holds no readable code.
    $this->get(route('account.security'))->assertDontSee('data-recovery-codes', false)->assertSee('data-codes-left', false);
    $stored = DB::table('users')->where('id', $admin->id)->value('two_factor_recovery_codes');
    foreach ($m[1] as $code) {
        expect($stored)->not->toContain(str_replace('-', '', $code))->not->toContain($code);
    }
    expect(app(TwoFactor::class)->recoveryCodesLeft($admin->fresh()))->toBe(8);

    // New codes replace the old ones.
    $old = str_replace('-', '', $m[1][0]);
    $this->post(route('account.security.recovery-codes'), ['password' => TF_PASSWORD]);
    expect(app(TwoFactor::class)->useRecoveryCode($admin->fresh(), $old))->toBeFalse();
});

it('signs in with a recovery code, once, in any letter case', function () {
    Mail::fake();
    $admin = tfUser();
    $codes = app(TwoFactor::class)->generateRecoveryCodes($admin);

    tfSignIn($admin)->assertRedirect(route('two-factor.challenge'));
    $this->post(route('two-factor.verify'), ['code' => 'aaaaa-bbbbb'])->assertSessionHasErrors('code');
    $this->post(route('two-factor.verify'), ['code' => strtoupper($codes[0])])->assertRedirect(route('account.security'));
    $this->assertAuthenticatedAs($admin);
    $this->assertDatabaseHas('system_logs', ['user_id' => $admin->id, 'action' => 'two_factor_recovery_used']);
    expect(app(TwoFactor::class)->recoveryCodesLeft($admin->fresh()))->toBe(7);

    $this->post(route('logout'));
    tfSignIn($admin);
    $this->post(route('two-factor.verify'), ['code' => $codes[0]])->assertSessionHasErrors('code'); // used up
    $this->assertGuest();
    $this->post(route('two-factor.verify'), ['code' => $codes[1]])->assertRedirect();
    $this->assertAuthenticatedAs($admin);
});

it('keeps admins out of switching it off, and lets others turn it off with their password', function () {
    $admin = tfUser();
    $this->actingAs($admin)->post(route('account.security.disable'), ['password' => TF_PASSWORD])->assertForbidden();
    $this->post(route('account.security.enable'), ['password' => TF_PASSWORD])->assertForbidden();

    $coordinator = tfUser(UserRole::Coordinator);
    $this->actingAs($coordinator)->post(route('account.security.enable'), ['password' => 'wrong'])->assertSessionHasErrors('password');
    $this->post(route('account.security.enable'), ['password' => TF_PASSWORD]);
    $this->post(route('account.security.disable'), ['password' => TF_PASSWORD])->assertRedirect();
    expect($coordinator->fresh()->two_factor_enabled)->toBeFalse()->and($coordinator->fresh()->two_factor_recovery_codes)->toBeNull();
    $this->actingAs(tfUser(UserRole::Student))->get(route('account.security'))->assertOk();
    $this->post(route('account.security.enable'), ['password' => TF_PASSWORD])->assertForbidden();
});

/* ---------- Email is down ---------- */

it('still gets an admin in when email is down: with a recovery code, and after user:reset-2fa', function () {
    $admin = tfUser();
    $codes = app(TwoFactor::class)->generateRecoveryCodes($admin);
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP server is down'));

    // The page says what happened and points to the recovery codes.
    tfSignIn($admin)->assertRedirect(route('two-factor.challenge'));
    $this->get(route('two-factor.challenge'))->assertOk()->assertSee('data-two-factor-state="failed"', false)->assertSee('recovery codes');
    $this->assertGuest();
    expect(User::find($admin->id)->two_factor_code_hash)->toBeNull(); // no code is left behind that was never sent

    // Way 1: a recovery code.
    $this->post(route('two-factor.verify'), ['code' => $codes[0]])->assertRedirect(route('account.security'));
    $this->assertAuthenticatedAs($admin);
    $this->post(route('logout'));

    // Way 2: the server operator resets it (here the recovery codes are also gone).
    $this->artisan('user:reset-2fa', ['login' => strtoupper($admin->email)])
        ->expectsOutputToContain('was reset')->expectsOutputToContain('once')->assertSuccessful();
    expect($admin->fresh()->two_factor_recovery_codes)->toBeNull()->and($admin->fresh()->two_factor_skip_once)->toBeTrue();

    tfSignIn($admin)->assertRedirect(route('account.security'));
    $this->assertAuthenticatedAs($admin);
    $this->get(route('account.security'))->assertSee('You have no recovery codes');
    $this->get(route('account.devices'))->assertSee('Make recovery codes'); // the banner on any other page
    expect($admin->fresh()->two_factor_skip_once)->toBeFalse();
    $this->assertDatabaseHas('system_logs', ['action' => 'two_factor_skipped']);
    $this->assertDatabaseHas('system_logs', ['action' => 'two_factor_reset']);

    // The pass works once: the next sign-in asks for a code again.
    $this->post(route('logout'));
    tfSignIn($admin)->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
});

it('resets optional two-factor off for ordinary staff, clears trusted browsers, and rejects unknown accounts', function () {
    Mail::fake();
    $coordinator = tfUser(UserRole::Coordinator, ['two_factor_enabled' => true]);
    app(TwoFactor::class)->generateRecoveryCodes($coordinator);
    DB::table('two_factor_trusted_browsers')->insert(['user_id' => $coordinator->id, 'token_hash' => str_repeat('a', 64), 'expires_at' => now()->addDays(5), 'created_at' => now()]);

    $this->artisan('user:reset-2fa', ['login' => $coordinator->email])->expectsOutputToContain('now off')->assertSuccessful();
    $coordinator->refresh();
    expect($coordinator->two_factor_enabled)->toBeFalse()->and($coordinator->two_factor_recovery_codes)->toBeNull()->and($coordinator->two_factor_skip_once)->toBeFalse()
        ->and(DB::table('two_factor_trusted_browsers')->where('user_id', $coordinator->id)->count())->toBe(0);
    tfSignIn($coordinator)->assertRedirect(route($coordinator->dashboardRoute())); // no code needed any more

    $this->artisan('user:reset-2fa', ['login' => 'nobody@scc.edu.ph'])->assertFailed();
});

/* ---------- Trusted browsers ---------- */

it('remembers a browser for 30 days, and forgets it when the password changes or on request', function () {
    Mail::fake();
    $admin = tfUser();
    tfSignIn($admin);

    $response = $this->post(route('two-factor.verify'), ['code' => tfLastCode(), 'trust' => '1']);
    $cookie = $response->getCookie(TwoFactor::TRUST_COOKIE);
    expect($cookie)->not->toBeNull()->and($cookie->getExpiresTime())->toBeGreaterThan(now()->addDays(29)->getTimestamp())->and($cookie->isHttpOnly())->toBeTrue();
    expect(DB::table('two_factor_trusted_browsers')->where('user_id', $admin->id)->count())->toBe(1)
        ->and(DB::table('two_factor_trusted_browsers')->value('token_hash'))->not->toBe($cookie->getValue());

    $this->post(route('logout'));
    $this->withCookie(TwoFactor::TRUST_COOKIE, $cookie->getValue());
    tfSignIn($admin)->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin);
    $this->assertDatabaseHas('system_logs', ['user_id' => $admin->id, 'action' => 'two_factor_skipped']);

    // Someone else's browser token, or one for another account, is not trusted.
    $this->post(route('logout'));
    $other = tfUser();
    tfSignIn($other)->assertRedirect(route('two-factor.challenge'));
    $this->post(route('logout'));

    // "Forget all trusted browsers", and a changed password, both end the trust.
    $this->actingAs($admin)->post(route('account.security.forget-browsers'))->assertRedirect();
    expect(DB::table('two_factor_trusted_browsers')->count())->toBe(0);
    $this->post(route('logout'));
    tfSignIn($admin)->assertRedirect(route('two-factor.challenge'));

    $this->post(route('two-factor.verify'), ['code' => tfLastCode(), 'trust' => '1']);
    expect(DB::table('two_factor_trusted_browsers')->count())->toBe(1);
    $admin->forceFill(['password' => 'Another-Password-7'])->save();
    expect(DB::table('two_factor_trusted_browsers')->count())->toBe(0);
});

/* ---------- Reminder banner ---------- */

it('reminds staff to switch it on, and admins to save recovery codes', function () {
    $coordinator = tfUser(UserRole::Coordinator);
    $this->actingAs($coordinator)->get(route('account.devices'))->assertSee('data-two-factor-banner', false)->assertSee('Protect your account')->assertSee('data-banner-close', false);
    $this->get(route('account.security'))->assertDontSee('data-two-factor-banner', false);

    $coordinator->forceFill(['two_factor_enabled' => true])->save();
    app(TwoFactor::class)->generateRecoveryCodes($coordinator);
    $this->get(route('account.devices'))->assertDontSee('data-two-factor-banner', false);

    $admin = tfUser();
    $this->actingAs($admin)->get(route('account.devices'))->assertSee('Save your recovery codes')->assertDontSee('data-banner-close', false); // not dismissible
    app(TwoFactor::class)->generateRecoveryCodes($admin);
    $this->get(route('account.devices'))->assertDontSee('data-two-factor-banner', false);

    // Not for students, demo accounts, or accounts that cannot get codes anyway.
    $this->actingAs(tfUser(UserRole::Student))->get(route('account.devices'))->assertDontSee('data-two-factor-banner', false);
    $this->actingAs(tfUser(UserRole::Coordinator, ['demo_account' => true, 'email' => 'a.b@gmail.com']))->get(route('account.devices'))->assertDontSee('data-two-factor-banner', false);
});

it('masks an email address for the challenge page', function () {
    expect(TwoFactorChallengeController::mask('registrar@scc.edu.ph'))->toBe('re*******@scc.edu.ph')
        ->and(TwoFactorChallengeController::mask('ab@x.com'))->toBe('ab**@x.com')
        ->and(TwoFactorChallengeController::mask('nonsense'))->toBe('');
});
