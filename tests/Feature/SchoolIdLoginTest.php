<?php

use App\Enums\UserRole;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('forces the email first (add, then 6-digit code), then the new password, before the dashboard', function () {
    \Illuminate\Support\Facades\Mail::fake();
    $institution = Institution::create(['name' => 'Test Institution', 'address' => null]);
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'email' => null,
        'password' => Hash::make('05142003'),
        'email_verified_at' => null,
        'must_change_password' => true,
    ]);
    Student::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'student_id_number' => 'SCC-26-00000009',
    ]);

    // 1. Sign in with the temporary password: the email step comes first.
    $this->post('/login', ['login' => 'scc-26-00000009', 'password' => '05142003'])
        ->assertRedirect(route('verification.notice'));
    $this->get('/student/dashboard')->assertRedirect(route('verification.notice'));
    $this->get(route('password.force-change'))->assertRedirect(route('verification.notice'));
    $this->get(route('verification.notice'))->assertOk()->assertSee('Add your')->assertSee('name="email"', false);

    // 2. Add the email: a code is sent.
    $this->post(route('verification.email'), ['email' => ' Student.Nine@Gmail.com '])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status');
    expect($user->fresh()->email)->toBe('student.nine@gmail.com')
        ->and($user->fresh()->hasVerifiedEmail())->toBeFalse();

    $code = null;
    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\EmailVerificationCodeMail::class, function ($mail) use (&$code) {
        $code = $mail->code;

        return $mail->hasTo('student.nine@gmail.com');
    });
    $this->get(route('verification.notice'))->assertSee('Verify your')->assertSee('Use a different one');

    // The password step stays locked until the code is entered.
    $this->put(route('password.force-change.update'), [
        'current_password' => '05142003',
        'password' => 'Mango!Tree#Sky27',
        'password_confirmation' => 'Mango!Tree#Sky27',
    ])->assertRedirect(route('verification.notice'));
    expect($user->fresh()->must_change_password)->toBeTrue();

    // 3. Enter the code: now the new password.
    $this->post(route('verification.verify'), ['code' => $code])->assertRedirect(route('password.force-change'));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->get('/student/dashboard')->assertRedirect(route('password.force-change'));
    $this->get(route('password.force-change'))->assertOk();

    // 4. Set the new password: the dashboard opens.
    $this->put(route('password.force-change.update'), [
        'current_password' => '05142003',
        'password' => 'Mango!Tree#Sky27',
        'password_confirmation' => 'Mango!Tree#Sky27',
    ])->assertRedirect(route('student.dashboard'));
    expect($user->fresh()->must_change_password)->toBeFalse();
    $this->get('/student/dashboard')->assertOk();
});

it('rejects an unknown school ID and wrong password with the renamed login field', function () {
    $institution = Institution::create(['name' => 'Test Institution', 'address' => null]);
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'email' => null,
        'password' => Hash::make('05142003'),
        'email_verified_at' => now(),
    ]);
    Student::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'student_id_number' => 'SCC-26-00000010',
    ]);

    $this->from('/login')->post('/login', [
        'login' => 'SCC-26-00000099',
        'password' => '05142003',
    ])->assertRedirect('/login')
        ->assertSessionHasErrors(['login' => 'Account not found.']);

    $this->from('/login')->post('/login', [
        'login' => 'SCC-26-00000010',
        'password' => 'WrongPassword',
    ])->assertRedirect('/login')
        ->assertSessionHasErrors(['login' => 'Wrong password.']);

    $this->assertGuest();
});

it('still logs in existing roles by email unchanged', function () {
    $employer = User::factory()->create([
        'role' => UserRole::Employer,
        'email' => 'employer.schoolid-regression@example.test',
        'password' => 'Password',
    ]);

    $this->post('/login', [
        'login' => $employer->email,
        'password' => 'Password',
    ])->assertRedirect(route('employer.dashboard'));
});
