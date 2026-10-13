<?php

use App\Enums\UserRole;
use App\Mail\EmailVerificationCodeMail;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\AcademicCatalogSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Routing\Middleware\ThrottleRequests;

it('shows a streamlined student-only registration form', function () {
	$this->get('/register')
		->assertOk()
		->assertSee('href="'.route('home').'"', false)
		->assertSee('Back to Home')
		->assertDontSee('Internship Profile')
		->assertDontSee('Choose your role in Skill Bridge');
});

it('shows a back-to-home link on the login page', function () {
	$this->get('/login')
		->assertOk()
		->assertSee('href="'.route('home').'"', false)
		->assertSee('Back to Home');
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
	$student = Student::query()->where('user_id', auth()->id())->firstOrFail();
	expect($student->profile_completion)->toBe($student->profileCompletionSummary()['percentage']);

	Mail::assertSent(EmailVerificationCodeMail::class, 1);
});

it('validates and saves every offered program during registration', function () {
	Mail::fake();
	$this->withoutMiddleware(ThrottleRequests::class);

	$institution = Institution::create([
		'name' => "St. Cecilia's College-Cebu, Inc.",
		'address' => null,
	]);
	app(AcademicCatalogSeeder::class)->run();

	$programs = Program::query()->orderBy('code')->get();
	expect($programs)->not->toBeEmpty();

	foreach ($programs as $index => $program) {
		$email = "program-choice-{$index}@example.test";

		$this->post('/register', [
			'name' => 'Program Choice Student',
			'email' => $email,
			'password' => 'Password',
			'password_confirmation' => 'Password',
			'institution_id' => $institution->id,
			'program_id' => $program->id,
		])->assertRedirect('/email/verify')
			->assertSessionHasNoErrors();

		$studentUser = User::query()->where('email', $email)->firstOrFail();
		$student = Student::query()->where('user_id', $studentUser->id)->firstOrFail();
		expect($student->program_id)->toBe($program->id)
			->and($student->program)->toBe($program->display_name);

		Auth::logout();
	}
});

it('rejects missing or unknown program selections during registration', function () {
	$institution = Institution::create([
		'name' => "St. Cecilia's College-Cebu, Inc.",
		'address' => null,
	]);
	$payload = [
		'name' => 'Invalid Program Student',
		'email' => 'invalid-program@example.test',
		'password' => 'Password',
		'password_confirmation' => 'Password',
		'institution_id' => $institution->id,
	];

	$this->post('/register', $payload)
		->assertSessionHasErrors(['program', 'program_id']);

	$this->post('/register', [...$payload, 'program_id' => 0])
		->assertSessionHasErrors('program_id');

	expect(User::query()->where('email', $payload['email'])->exists())->toBeFalse();
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
		$this->actingAs($user)->get($path)
			->assertOk()
			->assertSee('type="module"', false)
			->assertSee('assets/app-', false);
	}
});
