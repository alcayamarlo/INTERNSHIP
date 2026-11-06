<?php

use App\Enums\UserRole;
use App\Mail\EmailVerificationCodeMail;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/** An imported student: no email yet, birthday password that must be changed. */
function importedStudent(): User
{
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'email' => null,
        'email_verified_at' => null,
        'password' => Hash::make('Birthday#2004'),
        'must_change_password' => true,
    ]);
    Student::create(['user_id' => $user->id, 'student_id_number' => 'SCC-26-'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT)]);

    return $user;
}

/** Runs the first-login onboarding: new password, add email, enter the emailed code. */
function onboard($test, User $user, string $email): void
{
    Mail::fake();

    $test->post('/login', ['login' => $user->student->student_id_number, 'password' => 'Birthday#2004'])
        ->assertRedirect(route('password.force-change'));
    $test->put(route('password.force-change.update'), [
        'current_password' => 'Birthday#2004',
        'password' => 'Tr0ub4dor&Lantern',
        'password_confirmation' => 'Tr0ub4dor&Lantern',
    ])->assertRedirect(route('verification.notice'));
    $test->post(route('verification.email'), ['email' => $email])->assertRedirect(route('verification.notice'));

    $code = null;
    Mail::assertSent(EmailVerificationCodeMail::class, function ($mail) use (&$code) {
        $code = $mail->code;

        return true;
    });
    $test->post(route('verification.verify'), ['code' => $code])->assertRedirect(route('student.dashboard'));
}

it('asks for email verification only on the first login', function () {
    $user = importedStudent();
    onboard($this, $user, 'first.login@gmail.com');

    $fresh = $user->fresh();
    expect($fresh->hasVerifiedEmail())->toBeTrue()
        ->and($fresh->email_verified_at)->not->toBeNull();

    // Second, third... login: straight to the dashboard, no verify step.
    foreach (range(1, 2) as $ignored) {
        $this->post(route('logout'));
        $this->post('/login', ['login' => 'first.login@gmail.com', 'password' => 'Tr0ub4dor&Lantern'])
            ->assertRedirect(route('student.dashboard'));
        $this->get(route('student.dashboard'))->assertOk();
        $this->get(route('verification.notice'))->assertRedirect(route('student.dashboard'));
    }

    // Signing in with the school ID instead of the email behaves the same.
    $this->post(route('logout'));
    $this->post('/login', ['login' => $user->student->student_id_number, 'password' => 'Tr0ub4dor&Lantern'])
        ->assertRedirect(route('student.dashboard'));
});

/** The profile Contact tab, as the page's ajax save sends it. */
function contactTab(string $email): array
{
    return [
        'profile_section' => 'contact',
        'privacy_consent' => '1',
        'email' => $email,
        'phone' => '09171234567',
        'address' => '45 Osmena Blvd',
        'city' => 'Cebu City',
        'province' => 'Cebu',
        'zip_code' => '6000',
        'emergency_contact_name' => 'Maria Cruz',
        'emergency_contact_relationship' => 'Mother',
        'emergency_contact_address' => '45 Osmena Blvd',
        'emergency_contact_phone' => '09181112222',
    ];
}

it('keeps the student verified when the profile is saved with the same email', function () {
    $user = importedStudent();
    onboard($this, $user, 'same.address@gmail.com');

    $this->putJson(route('student.profile.update'), contactTab('SAME.Address@gmail.com'))
        ->assertOk()
        ->assertJsonMissingPath('redirect_url');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('requires verification again when the student changes their email, and sends a code to the new one', function () {
    $user = importedStudent();
    onboard($this, $user, 'old.address@gmail.com');
    Mail::fake();

    $this->putJson(route('student.profile.update'), contactTab('new.address@gmail.com'))
        ->assertOk()
        ->assertJsonPath('redirect_url', route('verification.notice'));

    $fresh = $user->fresh();
    expect($fresh->email)->toBe('new.address@gmail.com')
        ->and($fresh->hasVerifiedEmail())->toBeFalse()
        ->and($fresh->email_verified)->toBeFalse()
        ->and($fresh->email_verified_at)->toBeNull();
    Mail::assertSent(EmailVerificationCodeMail::class, fn ($mail) => $mail->hasTo('new.address@gmail.com'));

    // Server-side guard: every page and the next login go to the code step until it is verified.
    $this->get(route('student.dashboard'))->assertRedirect(route('verification.notice'));
    $this->post(route('logout'));
    $this->post('/login', ['login' => 'new.address@gmail.com', 'password' => 'Tr0ub4dor&Lantern'])
        ->assertRedirect(route('verification.notice'));

    // Verifying the new address lets them straight in again.
    $code = null;
    Mail::assertSent(EmailVerificationCodeMail::class, function ($mail) use (&$code) {
        $code = $mail->code;

        return true;
    });
    $this->post(route('verification.verify'), ['code' => $code])->assertRedirect(route('student.dashboard'));
    expect($user->fresh()->email_verified)->toBeTrue();
});

it('drops verification when an admin types a different email for a student', function () {
    $user = importedStudent();
    onboard($this, $user, 'student.own@gmail.com');

    // Any save path (admin edit, tinker, import update) goes through the same model rule.
    $user->fresh()->update(['email' => 'typed.by.admin@gmail.com']);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();

    // Admin "Verify email" in the same save is respected.
    $user->fresh()->forceFill(['email' => 'vouched@gmail.com', 'email_verified_at' => now()])->save();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('does not touch verification for non-student accounts', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer, 'email' => 'hr@company.test']);
    $employer->update(['email' => 'hiring@company.test']);

    expect($employer->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('repairs existing students with the data migration', function () {
    $verifiedByCode = User::factory()->create(['role' => UserRole::Student, 'email' => 'proved@gmail.com', 'email_verified_at' => null]);
    \App\Models\SystemLog::create(['user_id' => $verifiedByCode->id, 'action' => 'email_verified', 'details' => ['email' => 'proved@gmail.com']]);

    $changedAfter = User::factory()->create(['role' => UserRole::Student, 'email' => 'changed@gmail.com', 'email_verified_at' => null]);
    \App\Models\SystemLog::create(['user_id' => $changedAfter->id, 'action' => 'email_verified', 'details' => ['email' => 'older@gmail.com']]);

    $legacyImport = User::factory()->create(['role' => UserRole::Student, 'email' => null, 'email_verified_at' => now()]);

    (require database_path('migrations/2026_11_06_000004_repair_student_email_verification.php'))->up();

    expect($verifiedByCode->fresh()->email_verified_at)->not->toBeNull()
        // Proof was for a different address, so it does not count.
        ->and($changedAfter->fresh()->email_verified_at)->toBeNull()
        ->and($legacyImport->fresh()->email_verified_at)->toBeNull();
});

it('sends a student with a temporary password to the password page before the verify page', function () {
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'email' => 'verified@gmail.com',
        'password' => Hash::make('Birthday#2004'),
        'must_change_password' => true,
    ]);
    Student::create(['user_id' => $user->id]);

    $this->post('/login', ['login' => 'verified@gmail.com', 'password' => 'Birthday#2004'])
        ->assertRedirect(route('password.force-change'));
    // The guard holds them on the password page even if they type the dashboard URL.
    $this->get(route('student.dashboard'))->assertRedirect(route('password.force-change'));
});
