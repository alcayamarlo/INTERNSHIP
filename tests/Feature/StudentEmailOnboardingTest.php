<?php

use App\Enums\UserRole;
use App\Mail\EmailVerificationCodeMail;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

function studentWithoutEmail(array $attributes = []): User
{
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'email' => null,
        'password' => Hash::make('Password#2026'),
        'email_verified_at' => now(),
        'must_change_password' => false,
        ...$attributes,
    ]);
    Student::create(['user_id' => $user->id, 'student_id_number' => 'SCC-26-'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT)]);

    return $user;
}

it('stops an existing student without an email at their next sign-in', function () {
    $user = studentWithoutEmail();

    $this->post('/login', ['login' => $user->student->student_id_number, 'password' => 'Password#2026'])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'Add your email address to finish setting up your account.');

    $this->get(route('student.dashboard'))->assertRedirect(route('verification.notice'));
});

it('rejects an invalid or already used email', function () {
    User::factory()->create(['email' => 'taken@gmail.com']);
    $user = studentWithoutEmail();

    $this->actingAs($user)->post(route('verification.email'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');
    $this->actingAs($user)->post(route('verification.email'), ['email' => 'TAKEN@gmail.com'])
        ->assertSessionHasErrors(['email' => 'This email is already used by another account. Use a different one.']);

    expect($user->fresh()->email)->toBeNull();
});

it('lets the student switch to a different email before verifying, and only the newest code works', function () {
    Mail::fake();
    $user = studentWithoutEmail();

    $this->actingAs($user)->post(route('verification.email'), ['email' => 'typo@gmial.com']);
    $this->get(route('verification.notice', ['change' => 1]))->assertSee('Add your')->assertSee('value="typo@gmial.com"', false);
    $this->post(route('verification.email'), ['email' => 'right@gmail.com']);

    $codes = [];
    Mail::assertSent(EmailVerificationCodeMail::class, function ($mail) use (&$codes) {
        $codes[$mail->to[0]['address']] = $mail->code;

        return true;
    });

    if ($codes['typo@gmial.com'] !== $codes['right@gmail.com']) {
        $this->post(route('verification.verify'), ['code' => $codes['typo@gmial.com']])->assertSessionHasErrors('code');
    }
    $this->post(route('verification.verify'), ['code' => $codes['right@gmail.com']])->assertRedirect(route('student.dashboard'));

    expect($user->fresh()->email)->toBe('right@gmail.com')->and($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertDatabaseHas('system_logs', ['user_id' => $user->id, 'action' => 'email_verified']);
});

it('does not let a verified user or a non-student change their email here', function () {
    $student = User::factory()->create(['role' => UserRole::Student, 'email' => 'done@gmail.com']);
    Student::create(['user_id' => $student->id]);
    $this->actingAs($student)->post(route('verification.email'), ['email' => 'other@gmail.com'])->assertRedirect(route('student.dashboard'));
    expect($student->fresh()->email)->toBe('done@gmail.com');

    $employer = User::factory()->unverified()->create(['role' => UserRole::Employer, 'email' => 'hr@company.test']);
    $this->actingAs($employer)->post(route('verification.email'), ['email' => 'else@gmail.com'])->assertForbidden();
    $this->get(route('verification.notice'))->assertDontSee('Use a different one');
    expect($employer->fresh()->email)->toBe('hr@company.test');
});

it('keeps the email and shows a resend message when the code cannot be sent', function () {
    $user = studentWithoutEmail();
    $this->mock(\App\Services\MailSender::class)->shouldReceive('send')->andReturnFalse();

    $this->actingAs($user)->post(route('verification.email'), ['email' => 'later@gmail.com'])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHasErrors('code');

    expect($user->fresh()->email)->toBe('later@gmail.com');
});

it('sends the student back to the email step when resending with no email on file', function () {
    $user = studentWithoutEmail();

    $this->actingAs($user)->post(route('verification.resend'))->assertRedirect(route('verification.notice'));
});

it('shows No email yet to the admin and refuses to mark such an account verified', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $user = studentWithoutEmail(['name' => 'Nina Noemail']);

    $this->actingAs($admin)->get(route('admin.users.index'))->assertSee('No email yet');
    $this->actingAs($admin)->post(route('admin.users.verify-email', $user))->assertSessionHas('error');
});
