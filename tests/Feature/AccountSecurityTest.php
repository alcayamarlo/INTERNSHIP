<?php

use App\Enums\UserRole;
use App\Http\Controllers\AccountDevicesController;
use App\Http\Middleware\EndIdleSession;
use App\Models\AppNotification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// The suite turns the idle sign-out off (phpunit.xml); these tests are about it.
beforeEach(fn () => config(['security.idle_timeout' => true]));

function asUser(UserRole $role = UserRole::Student, array $attributes = []): User
{
    return User::factory()->create(['role' => $role, 'password' => 'Correct-Horse-9', 'email_verified_at' => now(), 'must_change_password' => false] + $attributes);
}

function wrongLogin(string $login): \Illuminate\Testing\TestResponse
{
    // A fresh address each time: the per-address limit (5 a minute) must not hide the per-account lock.
    return test()->withServerVariables(['REMOTE_ADDR' => '10.0.'.random_int(0, 250).'.'.random_int(1, 250)])
        ->from(route('login'))->post(route('login'), ['login' => $login, 'password' => 'wrong-password']);
}

/* ---------- Lockout ---------- */

it('locks an account after 10 wrong passwords from any address, even against the right password', function () {
    $user = asUser();

    foreach (range(1, 9) as $try) {
        wrongLogin($user->email)->assertSessionHasErrors(['login' => \App\Http\Controllers\Auth\LoginController::FAILED_MESSAGE]);
    }
    expect($user->fresh()->failed_logins)->toBe(9)->and($user->fresh()->locked_until)->toBeNull();

    wrongLogin($user->email)->assertSessionHasErrors('login');
    $user->refresh();
    expect($user->locked_until)->not->toBeNull()
        ->and($user->locked_until->between(now()->addMinutes(14), now()->addMinutes(16)))->toBeTrue();
    expect(session('errors')->first('login'))->toContain('locked until');

    // The right password is refused while locked.
    $this->post(route('login'), ['login' => $user->email, 'password' => 'Correct-Horse-9'])->assertSessionHasErrors('login');
    $this->assertGuest();

    // The owner is told, and both events are in the security log.
    expect(AppNotification::where('user_id', $user->id)->where('type', 'account_security')->count())->toBe(1);
    $this->assertDatabaseHas('system_logs', ['user_id' => $user->id, 'action' => 'account_locked']);
    $this->assertDatabaseHas('system_logs', ['user_id' => $user->id, 'action' => 'login_blocked']);

    // After 15 minutes the lock ends by itself and the right password works; the count starts again.
    $this->travel(16)->minutes();
    $this->post(route('login'), ['login' => $user->email, 'password' => 'Correct-Horse-9'])->assertRedirect();
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->failed_logins)->toBe(0)->and($user->fresh()->locked_until)->toBeNull();
});

it('only counts wrong passwords within 15 minutes of each other', function () {
    $user = asUser();
    foreach (range(1, 8) as $try) {
        wrongLogin($user->email);
    }
    $this->travel(20)->minutes();
    foreach (range(1, 3) as $try) {
        wrongLogin($user->email);
    }

    expect($user->fresh()->locked_until)->toBeNull()->and($user->fresh()->failed_logins)->toBe(3);
});

it('locks by school ID as well, and unknown accounts never lock', function () {
    $user = asUser();
    Student::create(['user_id' => $user->id, 'student_id_number' => 'SCC-23-00099999', 'profile_completion' => 0]);
    foreach (range(1, 10) as $try) {
        wrongLogin('scc-23-00099999');
    }
    expect($user->fresh()->locked_until)->not->toBeNull();

    foreach (range(1, 12) as $try) {
        wrongLogin('nobody@example.com')->assertSessionHasErrors(['login' => \App\Http\Controllers\Auth\LoginController::FAILED_MESSAGE]);
    }
});

it('lets an admin unlock an account from the Users page, and the command does the same', function () {
    $admin = asUser(UserRole::Admin);
    $locked = asUser(UserRole::Coordinator, ['locked_until' => now()->addMinutes(10)]);

    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('data-locked', false)->assertSee(route('admin.users.unlock', $locked), false);
    $this->actingAs(asUser(UserRole::Coordinator))->post(route('admin.users.unlock', $locked))->assertForbidden();

    $this->actingAs($admin)->post(route('admin.users.unlock', $locked))->assertRedirect()->assertSessionHas('success');
    expect($locked->fresh()->locked_until)->toBeNull();
    $this->assertDatabaseHas('system_logs', ['user_id' => $admin->id, 'action' => 'account_unlocked']);

    // fresh(): a stale copy with the same second-precision value would see "no change" and skip the write.
    $locked = $locked->fresh();
    $locked->forceFill(['locked_until' => now()->addMinutes(10), 'failed_logins' => 0])->save();
    $this->artisan('user:unlock', ['login' => strtoupper($locked->email)])->expectsOutputToContain('is unlocked')->assertSuccessful();
    expect($locked->fresh()->locked_until)->toBeNull();
    $this->artisan('user:unlock', ['login' => $locked->email])->expectsOutputToContain('was not locked')->assertSuccessful();
    $this->artisan('user:unlock', ['login' => 'nobody@example.com'])->assertFailed();
});

/* ---------- Your devices ---------- */

it('lists where the account is signed in and signs out the other devices after the password', function () {
    $user = asUser(UserRole::Coordinator);
    $other = asUser();
    $agent = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';
    DB::table('sessions')->insert([
        ['id' => 'phone-session', 'user_id' => $user->id, 'ip_address' => null, 'user_agent' => $agent, 'payload' => '', 'last_activity' => now()->subHour()->getTimestamp()],
        ['id' => 'someone-else', 'user_id' => $other->id, 'ip_address' => null, 'user_agent' => $agent, 'payload' => '', 'last_activity' => now()->getTimestamp()],
    ]);

    $this->actingAs($user)->get(route('account.devices'))->assertOk()->assertSee('Chrome on Android')->assertSee('Sign out other devices');

    $this->delete(route('account.devices.destroy-others'), ['password' => 'wrong'])->assertSessionHasErrors('password');
    expect(DB::table('sessions')->where('id', 'phone-session')->exists())->toBeTrue();

    $this->delete(route('account.devices.destroy-others'), ['password' => 'Correct-Horse-9'])->assertRedirect(route('account.devices'))->assertSessionHas('success');
    expect(DB::table('sessions')->where('id', 'phone-session')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'someone-else')->exists())->toBeTrue();
    $this->assertDatabaseHas('system_logs', ['user_id' => $user->id, 'action' => 'sessions_revoked']);

    expect(AccountDevicesController::describe('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0 Safari/537.36 Edg/130.0'))->toBe('Edge on Windows')
        ->and(AccountDevicesController::describe(''))->toBe('Unknown device');
});

it('signs out every other device when a password changes', function () {
    config(['session.driver' => 'database']);
    $user = asUser();
    DB::table('sessions')->insert(['id' => 'old-laptop', 'user_id' => $user->id, 'ip_address' => null, 'user_agent' => 'x', 'payload' => '', 'last_activity' => now()->getTimestamp()]);

    $user->forceFill(['name' => 'Renamed'])->save();
    expect(DB::table('sessions')->where('id', 'old-laptop')->exists())->toBeTrue();

    $user->forceFill(['password' => 'Another-Password-7'])->save();
    expect(DB::table('sessions')->where('id', 'old-laptop')->exists())->toBeFalse();
});

/* ---------- Idle sign-out ---------- */

it('signs staff out after 30 idle minutes and students after 120', function () {
    $coordinator = asUser(UserRole::Coordinator);
    $this->actingAs($coordinator)->get(route('account.devices'))->assertOk();

    $this->travel(29)->minutes();
    $this->get(route('account.devices'))->assertOk(); // activity: the clock starts again
    $this->travel(31)->minutes();
    $this->get(route('account.devices'))->assertRedirect(route('login'))->assertSessionHas('status', fn (string $m) => str_contains($m, '30 minutes'));
    $this->assertGuest();
    $this->assertDatabaseHas('system_logs', ['user_id' => $coordinator->id, 'action' => 'logout_idle']);

    $student = asUser();
    $this->actingAs($student)->get(route('account.devices'))->assertOk();
    $this->travel(100)->minutes();
    $this->get(route('account.devices'))->assertOk();
    $this->travel(121)->minutes();
    $this->get(route('account.devices'))->assertRedirect(route('login'));
});

it('does not count the background idle check as activity, and Stay signed in does', function () {
    $this->freezeTime(); // exact seconds are asserted below
    $this->actingAs(asUser(UserRole::Employer))->get(route('account.devices'))->assertOk();

    $this->travel(29)->minutes();
    $this->getJson(route('session.idle-status'))->assertOk()->assertJson(['seconds_left' => 60]);
    $this->travel(30)->seconds();
    $this->getJson(route('session.idle-status'))->assertJson(['seconds_left' => 30]); // still counting down

    $this->postJson(route('session.keep-alive'))->assertOk()->assertJson(['seconds_left' => 1800]);

    $this->travel(31)->minutes();
    $this->getJson(route('session.idle-status'))->assertStatus(401)->assertJson(['signed_out' => true]);
});

it('tells the page how long is left', function () {
    $this->actingAs(asUser(UserRole::Admin))->get(route('account.devices'))
        ->assertSee('data-idle-timeout', false)->assertSee('data-seconds-left="1800"', false)->assertSee('js/idle-timeout.js', false);
    expect(EndIdleSession::limitFor(asUser()))->toBe(120);
});
