<?php

use App\Enums\UserRole;
use App\Mail\EmailVerificationCodeMail;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('shows a streamlined student-only registration form', function () {
	$this->get('/register')
		->assertOk()
		->assertDontSee('Internship Profile')
		->assertDontSee('Choose your role in Skill Bridge');
});

it('registers a student, logs them in, and sends one verification email', function () {
	Mail::fake();

	$institution = Institution::create([
		'name' => "St. Cecilia's College-Cebu, Inc.",
		'address' => null,
	]);

	$response = $this->post('/register', [
		'name' => 'Test Student',
		'email' => 'student.regression@example.com',
		'password' => 'Password',
		'password_confirmation' => 'Password',
		'phone' => '+639171234567',
		'institution_id' => $institution->id,
		'program' => 'Bachelor of Science in Information Technology',
	]);

	$response->assertRedirect('/email/verify');
	$response->assertSessionHas('status', 'A 6-digit verification code has been sent to your email address.');
	$this->assertAuthenticated();
	$this->assertDatabaseHas('users', [
		'email' => 'student.regression@example.com',
		'role' => UserRole::Student->value,
	]);

	Mail::assertSent(EmailVerificationCodeMail::class, 1);
});

it('redirects unverified users to email verification', function () {
	$user = User::factory()->unverified()->create([
		'role' => UserRole::Student,
	]);
	Student::create(['user_id' => $user->id]);

	$this->actingAs($user)
		->get('/student/dashboard')
		->assertRedirect('/email/verify');
});

it('allows a verified user to log in and access their dashboard', function () {
	$user = User::factory()->create([
		'role' => UserRole::Student,
		'email' => 'verified.login@example.com',
		'password' => 'Password',
		'email_verified_at' => now(),
	]);
	Student::create(['user_id' => $user->id]);

	$this->post('/login', [
		'email' => $user->email,
		'password' => 'Password',
	])->assertRedirect('/student/dashboard');

	$this->get('/student/dashboard')->assertOk();
});

it('loads role-based dashboards for authenticated users', function () {
	$institution = Institution::create(['name' => 'Test Institution', 'address' => 'Cebu City']);

	$student = User::factory()->create(['role' => UserRole::Student, 'is_active' => true]);
	Student::create(['user_id' => $student->id, 'institution_id' => $institution->id, 'program' => 'IT', 'profile_completion' => 20]);

	$employer = User::factory()->create(['role' => UserRole::Employer, 'is_active' => true]);
	Employer::create(['user_id' => $employer->id, 'company_name' => 'Test Company', 'contact_person' => $employer->name]);

	$coordinator = User::factory()->create(['role' => UserRole::Coordinator, 'is_active' => true]);
	Coordinator::create(['user_id' => $coordinator->id, 'institution_id' => $institution->id]);

	foreach ([[$student, '/student/dashboard'], [$employer, '/employer/dashboard'], [$coordinator, '/coordinator/dashboard']] as [$user, $path]) {
		$this->actingAs($user)->get($path)->assertOk();
	}
});
