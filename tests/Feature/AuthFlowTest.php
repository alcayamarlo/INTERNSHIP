<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;

it('shows a back-to-home link on the login page', function () {
	$this->get('/login')
		->assertOk()
		->assertSee('href="'.route('home').'"', false)
		->assertSee('Back to Home');
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
		'login' => $user->email,
		'password' => 'Password',
	])->assertRedirect('/student/dashboard');

	$this->get('/student/dashboard')->assertOk();
});

it('normalizes email on persistence and login', function () {
	$user = User::factory()->create([
		'role' => UserRole::Student,
		'email' => '  Student.Login@Example.Test ',
		'password' => 'Password',
		'email_verified_at' => null,
	]);
	Student::create(['user_id' => $user->id]);
	expect($user->email)->toBe('student.login@example.test');

	$this->post('/login', [
		'login' => ' STUDENT.LOGIN@EXAMPLE.TEST ',
		'password' => 'Password',
	])->assertRedirect('/email/verify')
		->assertSessionHas('status', 'Account not activated yet. Enter the 6-digit code sent to your email, or request a new one.');

	$this->assertAuthenticatedAs($user);
	$this->get('/student/dashboard')->assertRedirect('/email/verify');
});

it('returns distinct login errors and blocks archived accounts', function () {
	$user = User::factory()->create([
		'email' => 'archived.login@example.test',
		'password' => 'Password',
		'archived_at' => now(),
	]);

	$this->from('/login')->post('/login', [
		'login' => 'missing.login@example.test',
		'password' => 'Password',
	])->assertRedirect('/login')
		->assertSessionHasErrors(['login' => 'Account not found.']);

	$this->from('/login')->post('/login', [
		'login' => $user->email,
		'password' => 'WrongPassword',
	])->assertRedirect('/login')
		->assertSessionHasErrors(['login' => 'Wrong password.']);

	$this->from('/login')->post('/login', [
		'login' => $user->email,
		'password' => 'Password',
	])->assertRedirect('/login')
		->assertSessionHasErrors(['login' => 'This account has been archived. Please contact the administrator.']);

	$this->assertGuest();
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
		$this->actingAs($user)->get($path)
			->assertOk()
			->assertSee('type="module"', false)
			->assertSee('assets/app-', false);
	}
});
