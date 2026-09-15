<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;

it('shows a streamlined student-only registration form', function () {
	$this->get('/register')
		->assertOk()
		->assertDontSee('Internship Profile')
		->assertDontSee('Choose your role in Skill Bridge');
});

it('registers a student and redirects to the login form', function () {
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

	$response->assertRedirect('/login');
	$response->assertSessionHas('status', 'Your account has been created. Please log in to continue.');
	$this->assertGuest();
	$this->assertDatabaseHas('users', [
		'email' => 'student.regression@example.com',
		'role' => UserRole::Student->value,
	]);
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
