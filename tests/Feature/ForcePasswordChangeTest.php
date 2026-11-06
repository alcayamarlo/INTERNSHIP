<?php

use App\Enums\UserRole;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Models\Student;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

function firstLoginStudent(string $password = '05152008'): User
{
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'name' => 'Juan Dela Cruz',
        'email' => 'juan.dc@gmail.com',
        'password' => Hash::make($password),
        'must_change_password' => true,
    ]);
    Student::create(['user_id' => $user->id, 'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'date_of_birth' => '2008-05-15', 'student_id_number' => 'SCC-22-00012345']);

    return $user;
}

function changePassword(array $overrides = []): \Illuminate\Testing\TestResponse
{
    return test()->from(route('password.force-change'))->put(route('password.force-change.update'), [
        'current_password' => '05152008',
        'password' => 'Mango!Tree#Sky27',
        'password_confirmation' => 'Mango!Tree#Sky27',
        ...$overrides,
    ]);
}

it('blocks every protected page, direct URLs, and JSON calls until the password is changed', function () {
    $this->actingAs(firstLoginStudent());

    $this->get(route('student.dashboard'))->assertRedirect(route('password.force-change'));
    $this->get(route('student.profile.edit'))->assertRedirect(route('password.force-change'));
    $this->getJson(route('notifications.index'))->assertForbidden()->assertJsonPath('redirect', route('password.force-change'));
});

it('shows the form with the current password field, live checklist, and strength meter, and is not cached', function () {
    $this->actingAs(firstLoginStudent());

    $response = $this->get(route('password.force-change'))->assertOk()
        ->assertSee('name="current_password"', false)
        ->assertSee('data-pw-checklist', false)
        ->assertSee('data-pw-meter', false)
        ->assertSee('data-pw-submit disabled', false);
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('lists a friendly message for each broken rule, checked on the server', function () {
    $this->actingAs(firstLoginStudent());

    changePassword(['password' => 'juan200', 'password_confirmation' => 'juan200'])
        ->assertSessionHasErrors(['password' => 'Use 8 to 64 characters.'])
        ->assertSessionHasErrors(['password' => 'Add at least one uppercase letter (A-Z).'])
        ->assertSessionHasErrors(['password' => 'Add at least one special character, like ! @ # $ or %.']);

    changePassword(['password_confirmation' => 'Mango!Tree#Sky28'])->assertSessionHasErrors(['password' => 'The new passwords do not match.']);
    expect(session()->getOldInput())->not->toHaveKey('password');
});

it('locks the form and signs out after five wrong current passwords', function () {
    $user = firstLoginStudent();
    $this->actingAs($user);

    foreach (range(1, 4) as $attempt) {
        changePassword(['current_password' => '01012000'])->assertSessionHasErrors(['current_password' => 'Your current password is incorrect.']);
    }
    changePassword(['current_password' => '01012000'])->assertRedirect(route('login'))->assertSessionHasErrors('login');
    $this->assertGuest();
    expect(SystemLog::where('action', 'password_change_failed')->count())->toBe(5)
        ->and(SystemLog::where('action', 'password_change_locked')->exists())->toBeTrue();

    // Signing in again does not reset the lock, even with the right password.
    $this->actingAs($user);
    $this->get(route('password.force-change'))->assertSee('Too many incorrect attempts');
    changePassword()->assertSessionHasErrors('current_password');
    expect(Hash::check('05152008', $user->fresh()->password))->toBeTrue();

    RateLimiter::clear(ForcePasswordChangeController::lockKey($user->id));
});

it('saves a bcrypt hash, clears the flag, starts a fresh session, and logs the change without the password', function () {
    $user = firstLoginStudent();
    $user->forceFill(['email_verified_at' => now(), 'remember_token' => 'old-token'])->save();
    $this->actingAs($user);
    $oldSession = session()->getId();

    changePassword()->assertRedirect(route('student.dashboard'));

    $fresh = $user->fresh();
    expect($fresh->must_change_password)->toBeFalse()
        ->and($fresh->password)->not->toBe('Mango!Tree#Sky27')
        ->and(Hash::info($fresh->password)['algoName'])->toBe('bcrypt')
        ->and(Hash::check('Mango!Tree#Sky27', $fresh->password))->toBeTrue()
        ->and($fresh->remember_token)->not->toBe('old-token')
        ->and(session()->getId())->not->toBe($oldSession);

    $log = SystemLog::where('action', 'password_force_change')->firstOrFail();
    expect($log->user_id)->toBe($user->id)->and(json_encode($log->details))->not->toContain('Mango');
    $this->get(route('student.dashboard'))->assertOk();
});

it('limits sign-in attempts per account, so classmates on the same Wi-Fi are not blocked', function () {
    User::factory()->create(['email' => 'classmate@gmail.com', 'password' => Hash::make('Right!Pass#2026')]);

    foreach (range(1, 6) as $attempt) {
        $this->post('/login', ['login' => 'someone@gmail.com', 'password' => 'wrong']);
    }

    $this->post('/login', ['login' => 'classmate@gmail.com', 'password' => 'Right!Pass#2026'])->assertStatus(302)->assertSessionDoesntHaveErrors();
});
