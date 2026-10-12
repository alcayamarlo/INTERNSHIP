<?php

use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\Coordinator;
use App\Models\Institution;
use App\Models\Major;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicCalendar;
use Carbon\CarbonImmutable;
use Database\Seeders\AcademicCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the confirmed programs, majors, and editable default internship hours', function () {
    app(AcademicCatalogSeeder::class)->run();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect(Program::count())->toBe(5)
        ->and(Program::where('code', 'BSIT')->value('required_internship_hours'))->toBe(486)
        ->and(Program::where('code', 'BSHM')->value('required_internship_hours'))->toBe(600)
        ->and(Program::where('code', 'BSMA')->value('required_internship_hours'))->toBe(486)
        ->and(Program::where('code', 'BSBA')->first()->required_hours_confirmed)->toBeFalse()
        ->and(AcademicSetting::current()->student_id_pattern)->toBe('^SCC-\\d{2}-\\d{8}$')
        ->and(Program::where('code', 'BSBA')->first()->majors()->orderBy('name')->pluck('name')->all())->toBe([
            'Financial Management',
            'Human Resource Management',
            'Marketing Management',
            'Operations Management',
        ])
        ->and(Program::where('code', 'BSIT')->first()->majors()->count())->toBe(0)
        ->and(Program::where('name', 'like', '%Education%')->exists())->toBeFalse();

    $this->actingAs($admin)
        ->get(route('admin.academics.index'))
        ->assertOk()
        ->assertSee('css/admin-academics.css')
        ->assertSee('class="admin-academics-page"', false)
        ->assertSee('Student ID format')
        ->assertSee('Example: SCC-21-00013240.')
        ->assertSee('value="^SCC-\\d{2}-\\d{8}$"', false)
        ->assertSee('1st semester')
        ->assertSee('2nd semester')
        ->assertSee('Summer');
});

it('hides the Summer term date section when Summer is disabled', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    AcademicSetting::current()->update(['summer_enabled' => false]);

    $this->actingAs($admin)
        ->get(route('admin.academics.index'))
        ->assertOk()
        ->assertSee('1st semester')
        ->assertSee('2nd semester')
        ->assertDontSee('<h4 class="h6 mb-0">Summer</h4>', false)
        ->assertDontSee('name="summer_start_month"', false);
});

it('redirects the admin settings GET alias and sends successful saves to the academics page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $settings = AcademicSetting::current();
    $payload = $settings->only([
        'student_id_pattern',
        'academic_year_start_month',
        'summer_enabled',
        'current_term_override',
        'first_semester_start_month',
        'first_semester_start_day',
        'first_semester_end_month',
        'first_semester_end_day',
        'second_semester_start_month',
        'second_semester_start_day',
        'second_semester_end_month',
        'second_semester_end_day',
        'summer_start_month',
        'summer_start_day',
        'summer_end_month',
        'summer_end_day',
    ]);
    $payload['student_id_pattern'] = '^SCC-\d{2}-\d{8}$';

    $this->actingAs($admin)
        ->get(route('admin.academics.settings'))
        ->assertRedirect(route('admin.academics.index'));

    $this->post(route('admin.academics.settings.update'), $payload)
        ->assertRedirect(route('admin.academics.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Academic settings saved.');
});

it('generates school years and date-based terms from the editable calendar settings', function () {
    $settings = AcademicSetting::current();
    $calendar = app(AcademicCalendar::class);
    $cases = [
        ['2026-10-12', '2026-2027', 'first'],
        ['2027-01-15', '2026-2027', 'second'],
        ['2027-06-20', '2026-2027', 'summer'],
        ['2027-08-01', '2027-2028', 'first'],
        ['2027-07-31', '2026-2027', 'summer'],
    ];

    foreach ($cases as [$date, $schoolYear, $semester]) {
        expect($calendar->defaults(CarbonImmutable::parse($date), $settings))
            ->toBe(['school_year' => $schoolYear, 'semester' => $semester]);
    }

    expect($calendar->schoolYears(CarbonImmutable::parse('2026-10-12'), $settings))
        ->toBe(['2025-2026', '2026-2027', '2027-2028', '2028-2029', '2029-2030']);

    $settings->update(['summer_enabled' => false]);
    expect($calendar->defaults(CarbonImmutable::parse('2027-06-20'), $settings)['semester'])->toBe('second');

    $settings->update(['current_term_override' => 'first']);
    expect($calendar->defaults(CarbonImmutable::parse('2027-01-15'), $settings)['semester'])->toBe('first');
});

it('rejects overlapping or gapped academic term ranges in admin settings', function (array $overrides) {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $settings = AcademicSetting::current();
    $payload = $settings->only([
        'student_id_pattern',
        'academic_year_start_month',
        'summer_enabled',
        'current_term_override',
        'first_semester_start_month',
        'first_semester_start_day',
        'first_semester_end_month',
        'first_semester_end_day',
        'second_semester_start_month',
        'second_semester_start_day',
        'second_semester_end_month',
        'second_semester_end_day',
        'summer_start_month',
        'summer_start_day',
        'summer_end_month',
        'summer_end_day',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.academics.settings.update'), array_merge($payload, $overrides))
        ->assertSessionHasErrors('term_ranges');
})->with([
    'overlap' => [['summer_start_month' => 5, 'summer_start_day' => 1]],
    'gap' => [['second_semester_end_month' => 4, 'second_semester_end_day' => 30]],
]);

it('saves the full academic settings form with the default student ID regex without validation errors', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $settings = AcademicSetting::current();
    $payload = $settings->only([
        'student_id_pattern',
        'require_fourth_year_standing',
        'require_major_subjects_passed',
        'academic_year_start_month',
        'summer_enabled',
        'current_term_override',
        'first_semester_start_month',
        'first_semester_start_day',
        'first_semester_end_month',
        'first_semester_end_day',
        'second_semester_start_month',
        'second_semester_start_day',
        'second_semester_end_month',
        'second_semester_end_day',
        'summer_start_month',
        'summer_start_day',
        'summer_end_month',
        'summer_end_day',
    ]);
    $payload['student_id_pattern'] = '^SCC-\d{2}-\d{8}$';

    $response = $this->actingAs($admin)
        ->post(route('admin.academics.settings.update'), $payload)
        ->assertRedirect(route('admin.academics.index'))
        ->assertSessionHasNoErrors();

    expect($settings->fresh()->student_id_pattern)->toBe('^SCC-\d{2}-\d{8}$');
});

it('persists internship eligibility checkboxes and the student ID regex across reloads', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $settings = AcademicSetting::current();
    $payload = $settings->only([
        'student_id_pattern',
        'academic_year_start_month',
        'summer_enabled',
        'current_term_override',
        'first_semester_start_month',
        'first_semester_start_day',
        'first_semester_end_month',
        'first_semester_end_day',
        'second_semester_start_month',
        'second_semester_start_day',
        'second_semester_end_month',
        'second_semester_end_day',
        'summer_start_month',
        'summer_start_day',
        'summer_end_month',
        'summer_end_day',
    ]);
    $payload['student_id_pattern'] = '^SCC-\d{2}-\d{8}$';

    $this->actingAs($admin)
        ->post(route('admin.academics.settings.update'), [
            ...$payload,
            'require_fourth_year_standing' => '1',
            'require_major_subjects_passed' => '1',
        ])
        ->assertRedirect(route('admin.academics.index'))
        ->assertSessionHasNoErrors();

    expect($settings->fresh()->require_fourth_year_standing)->toBeTrue()
        ->and($settings->fresh()->require_major_subjects_passed)->toBeTrue()
        ->and($settings->fresh()->student_id_pattern)->toBe('^SCC-\d{2}-\d{8}$');

    $checkedPage = $this->get(route('admin.academics.index'))->assertOk()->getContent();
    expect($checkedPage)->toMatch('/id="require_fourth_year_standing"[^>]*checked/')
        ->and($checkedPage)->toMatch('/id="require_major_subjects_passed"[^>]*checked/');

    $this->post(route('admin.academics.settings.update'), $payload)
        ->assertRedirect(route('admin.academics.index'))
        ->assertSessionHasNoErrors();

    expect($settings->fresh()->require_fourth_year_standing)->toBeFalse()
        ->and($settings->fresh()->require_major_subjects_passed)->toBeFalse()
        ->and($settings->fresh()->student_id_pattern)->toBe('^SCC-\d{2}-\d{8}$');

    $uncheckedPage = $this->get(route('admin.academics.index'))->assertOk()->getContent();
    expect($uncheckedPage)->not->toMatch('/id="require_fourth_year_standing"[^>]*checked/')
        ->and($uncheckedPage)->not->toMatch('/id="require_major_subjects_passed"[^>]*checked/');
});

it('lets admins manage programs and prevents removing a major assigned to a student', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create(['user_id' => $coordinatorUser->id]);

    $this->actingAs($admin)
        ->post(route('admin.academics.programs.store'), [
            'name' => 'BS Business Administration',
            'code' => 'BSBA',
            'required_internship_hours' => 600,
            'required_hours_confirmed' => '1',
            'coordinator_id' => $coordinator->id,
            'majors' => "Marketing Management\nFinancial Management",
        ])
        ->assertRedirect(route('admin.academics.index'));

    $program = Program::where('code', 'BSBA')->firstOrFail();
    $major = Major::where('program_id', $program->id)->where('name', 'Marketing Management')->firstOrFail();
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    Student::create([
        'user_id' => $studentUser->id,
        'program_id' => $program->id,
        'major_id' => $major->id,
    ]);

    $this->put(route('admin.academics.programs.update', $program), [
        'name' => $program->name,
        'code' => $program->code,
        'required_internship_hours' => 600,
        'coordinator_id' => $coordinator->id,
        'majors' => 'Financial Management',
    ])->assertSessionHasErrors('majors');

    expect($major->fresh())->not->toBeNull()
        ->and($program->fresh()->required_hours_confirmed)->toBeTrue();
});

it('saves school academic fields against the selected program and major', function () {
    app(AcademicCatalogSeeder::class)->run();
    $program = Program::where('code', 'BSBA')->firstOrFail();
    $major = $program->majors()->firstOrFail();
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create(['user_id' => $coordinatorUser->id]);
    $program->update(['coordinator_id' => $coordinator->id]);
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'middle_name' => '',
            'last_name' => 'Student',
            'suffix' => '',
            'gender' => 'Female',
            'date_of_birth' => '2004-01-01',
            'email' => $user->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'student_number' => 'SCC-23-00012345',
            'program_id' => $program->id,
            'major_id' => $major->id,
            'year_level' => '4th Year',
            'section' => 'BSBA-4A',
            'school_year' => '2026-2027',
            'semester' => 'first',
            'expected_graduation' => '2028-05',
            'enrollment_status' => 'Regular',
            'gwa' => '1.75',
            'career_objectives' => 'Build professional experience.',
            'preferred_internship_field' => 'Finance',
            'preferred_work_setup' => 'Hybrid',
            'preferred_location' => 'Cebu',
        ])
        ->assertOk()
        ->assertJson(['message' => 'Profile saved']);

    $student->refresh();
    expect($student->program_id)->toBe($program->id)
        ->and($student->student_id_number)->toBe('SCC-23-00012345')
        ->and($student->major_id)->toBe($major->id)
        ->and($student->program)->toBe($program->display_name)
        ->and($student->school_year)->toBe('2026-2027')
        ->and($student->semester)->toBe('first')
        ->and($student->expected_graduation->format('Y-m-d'))->toBe('2028-05-01')
        ->and((float) $student->gwa)->toBe(1.75);

    $this->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('name="program_id"', false)
        ->assertSee('name="major_id"', false)
        ->assertSee('value="'.$coordinatorUser->name.'" readonly', false)
        ->assertSee('type="month"', false);
});

it('renders the school branding and database-backed academic profile fields', function () {
    app(AcademicCatalogSeeder::class)->run();
    $user = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('student.profile.edit'));

    $response->assertOk()
        ->assertSee("St. Cecilia's College-Cebu, Inc.", false)
        ->assertSee('name="student_number"', false)
        ->assertSee('name="program_id"', false)
        ->assertSee('name="major_id"', false)
        ->assertSee('name="year_level"', false)
        ->assertSee('name="section"', false)
        ->assertSee('name="school_year"', false)
        ->assertSee('name="semester"', false)
        ->assertSee('name="expected_graduation"', false)
        ->assertSee('name="enrollment_status"', false)
        ->assertSee('name="gwa"', false)
        ->assertSee('name="preferred_internship_field"', false)
        ->assertSee('name="preferred_work_setup"', false)
        ->assertSee('name="preferred_location"', false)
        ->assertSee('placeholder="SCC-21-00013240"', false)
        ->assertSee('BS Information Technology (BSIT)')
        ->assertDontSee('name="institution_id"', false);
});

it('only accepts configured student ID patterns and majors belonging to the selected program', function () {
    app(AcademicCatalogSeeder::class)->run();
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $studentUser->id]);
    $it = Program::where('code', 'BSIT')->firstOrFail();
    $ba = Program::where('code', 'BSBA')->firstOrFail();
    $baMajor = $ba->majors()->firstOrFail();

    $this->actingAs($studentUser)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'middle_name' => '',
            'last_name' => 'Student',
            'suffix' => '',
            'gender' => 'Female',
            'date_of_birth' => '2004-01-01',
            'email' => $studentUser->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'student_number' => 'BAD-ID',
            'program_id' => $it->id,
            'major_id' => $baMajor->id,
            'school_year' => '2035-2036',
            'semester' => 'summer',
            'expected_graduation' => '',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['student_number', 'major_id', 'school_year']);
});

it('accepts the configured student ID example and rejects an incorrect digit count', function (string $studentNumber, bool $accepted) {
    app(AcademicCatalogSeeder::class)->run();
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'student_number' => $studentNumber,
        ]);

    if ($accepted) {
        $response->assertOk()->assertJson(['message' => 'Profile saved']);
        expect($student->fresh()->student_id_number)->toBe($studentNumber);
    } else {
        $response->assertUnprocessable()->assertJsonValidationErrors('student_number');
    }
})->with([
    'valid example' => ['SCC-21-00013240', true],
    'too few digits' => ['SCC-21-1234', false],
]);

it('accepts student IDs with any two-digit year and normalizes their case and whitespace', function (string $studentNumber, string $expectedStudentNumber) {
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'student_number' => $studentNumber,
        ])
        ->assertOk()
        ->assertJson(['message' => 'Profile saved']);

    expect($student->fresh()->student_id_number)->toBe($expectedStudentNumber);
})->with([
    'year 19' => ['SCC-19-00012320', 'SCC-19-00012320'],
    'year 21' => ['SCC-21-00013240', 'SCC-21-00013240'],
    'year 09' => ['SCC-09-00000001', 'SCC-09-00000001'],
    'lowercase' => ['scc-19-00012320', 'SCC-19-00012320'],
    'surrounding whitespace' => [' SCC-19-00012320 ', 'SCC-19-00012320'],
]);

it('rejects invalid student ID regex settings and preserves valid backslashes on save', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $settings = AcademicSetting::current();
    $payload = $settings->toArray();
    $payload['student_id_pattern'] = '^SCC-(\d{2$';

    $invalidPatternResponse = $this->actingAs($admin)
        ->post(route('admin.academics.settings.update'), $payload)
        ->assertSessionHasErrors('student_id_pattern');

    expect($invalidPatternResponse->getSession()->get('errors')->getBag('default')->get('student_id_pattern'))
        ->toBe(['Enter a valid regular-expression pattern without delimiters.']);

    $this->get(route('admin.academics.index'))
        ->assertOk()
        ->assertSee('Student Id Pattern:')
        ->assertSee('Enter a valid regular-expression pattern without delimiters.')
        ->assertSee('id="student_id_pattern"', false);

    expect($settings->fresh()->student_id_pattern)->toBe('^SCC-\\d{2}-\\d{8}$');

    $payload['student_id_pattern'] = '^SCC-\\d{2}-\\d{8}$';
    $this->post(route('admin.academics.settings.update'), $payload)
        ->assertRedirect(route('admin.academics.index'))
        ->assertSessionHasNoErrors();

    expect($settings->fresh()->student_id_pattern)->toBe('^SCC-\\d{2}-\\d{8}$');

    $this->get(route('admin.academics.index'))
        ->assertOk()
        ->assertSee('value="^SCC-\\d{2}-\\d{8}$"', false);
});

it('rejects student IDs with a wrong prefix or digit count', function (string $studentNumber) {
    $user = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'student_number' => $studentNumber,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['student_number'])
        ->assertJsonPath('errors.student_number.0', 'Enter a student ID in the format SCC-21-00013240.');
})->with([
    'wrong prefix' => 'MSU-21-00013240',
    'one digit year' => 'SCC-9-00012320',
    'short year' => 'SCC-2-00013240',
    'short serial' => 'SCC-21-0001324',
    'long serial' => 'SCC-21-000132400',
    'short serial digits' => 'SCC-19-1234',
    'legacy format' => 'MSU-2024-001',
]);

it('rejects a duplicate student ID with a clear validation message', function () {
    $existingUser = User::factory()->create(['role' => UserRole::Student]);
    Student::create([
        'user_id' => $existingUser->id,
        'student_id_number' => 'SCC-21-00013240',
    ]);
    $user = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'student_number' => 'SCC-21-00013240',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.student_number.0', 'This student ID is already in use.');
});

it('saves blank GWA as null and renders the field empty', function (mixed $gwa) {
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'gwa' => $gwa,
        ])
        ->assertOk()
        ->assertJson(['message' => 'Profile saved']);

    expect($student->fresh()->getRawOriginal('gwa'))->toBeNull();

    $response = $this->get(route('student.profile.edit'))->assertOk();
    preg_match('/<input id="gwa"[^>]+>/', $response->getContent(), $matches);
    expect($matches[0] ?? '')->toContain('value=""');
})->with([
    'null' => [null],
    'blank' => [''],
]);

it('accepts GWA values on the confirmed 1-to-5 scale with at most two decimal places', function (string $gwa) {
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'gwa' => $gwa,
        ])
        ->assertOk()
        ->assertJson(['message' => 'Profile saved']);

    expect($student->fresh()->gwa)->toBe(number_format((float) $gwa, 2, '.', ''));

    $response = $this->get(route('student.profile.edit'))->assertOk();
    preg_match('/<input id="gwa"[^>]+>/', $response->getContent(), $matches);
    expect($matches[0] ?? '')->toContain('value="'.number_format((float) $gwa, 2, '.', '').'"');
})->with([
    'one' => '1',
    'one point seven five' => '1.75',
    'two point five' => '2.5',
    'five' => '5',
]);

it('rejects GWA values outside the confirmed scale or with more than two decimals', function (string $gwa) {
    $user = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'gwa' => $gwa,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.gwa.0', 'Enter a GWA between 1.00 and 5.00.');
})->with([
    'zero' => '0',
    'below range' => '0.99',
    'above range' => '5.01',
    'six' => '6',
    'not numeric' => 'abc',
    'negative' => '-1',
    'too many decimal places' => '1.755',
]);

it('allows a student to save other profile fields without changing an existing legacy ID', function () {
    app(AcademicCatalogSeeder::class)->run();
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $user->id,
        'student_id_number' => 'MSU-2024-001',
    ]);

    $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'student_number' => 'MSU-2024-001',
            'section' => 'BSIT-4A',
        ])
        ->assertOk()
        ->assertJson(['message' => 'Profile saved']);

    expect($student->fresh()->student_id_number)->toBe('MSU-2024-001')
        ->and($student->fresh()->section)->toBe('BSIT-4A');
});

it('allows a student to replace a legacy ID with a valid normalized ID', function () {
    app(AcademicCatalogSeeder::class)->run();
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $user->id,
        'student_id_number' => 'MSU-2024-001',
    ]);

    $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'student_number' => ' scc-21-00013240 ',
        ])
        ->assertOk()
        ->assertJson(['message' => 'Profile saved']);

    expect($student->fresh()->student_id_number)->toBe('SCC-21-00013240');
});

it('rejects changing a legacy ID to another invalid ID', function () {
    app(AcademicCatalogSeeder::class)->run();
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $user->id,
        'student_id_number' => 'MSU-2024-001',
    ]);

    $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Cecilia',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '+63 912 345 6789',
            'address' => 'Ward II',
            'city' => 'Minglanilla',
            'province' => 'Cebu',
            'zip_code' => '6046',
            'student_number' => 'SCC-21-123',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['student_number']);

    expect($student->fresh()->student_id_number)->toBe('MSU-2024-001');
});

it('shows and clears the correction notice as a student corrects a legacy ID', function () {
    app(AcademicCatalogSeeder::class)->run();
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $user->id,
        'student_id_number' => 'MSU-2024-001',
    ]);

    $response = $this->actingAs($user)
        ->get(route('student.profile.edit'))
        ->assertOk();
    preg_match('/<small id="studentIdCorrectionNotice"[^>]*>/', $response->getContent(), $matches);
    expect($matches[0] ?? '')->not->toContain('hidden');

    $this->putJson(route('student.profile.update'), [
        'first_name' => 'Cecilia',
        'last_name' => 'Student',
        'email' => $user->email,
        'phone' => '+63 912 345 6789',
        'address' => 'Ward II',
        'city' => 'Minglanilla',
        'province' => 'Cebu',
        'zip_code' => '6046',
        'student_number' => 'SCC-21-00013240',
    ])->assertOk()
        ->assertJsonPath('student_id_number', 'SCC-21-00013240')
        ->assertJsonPath('student_id_needs_correction', false);

    $response = $this->get(route('student.profile.edit'))->assertOk();
    preg_match('/<small id="studentIdCorrectionNotice"[^>]*>/', $response->getContent(), $matches);
    expect($matches[0] ?? '')->toContain('hidden');

    expect($student->fresh()->student_id_number)->toBe('SCC-21-00013240');
});

it('does not show the correction notice for a valid earlier-year student ID', function () {
    app(AcademicCatalogSeeder::class)->run();
    $user = User::factory()->create(['role' => UserRole::Student]);
    Student::create([
        'user_id' => $user->id,
        'student_id_number' => 'SCC-19-00012320',
        'year_level' => '4th Year',
    ]);

    $response = $this->actingAs($user)
        ->get(route('student.profile.edit'))
        ->assertOk();
    preg_match('/<small id="studentIdCorrectionNotice"[^>]*>/', $response->getContent(), $matches);
    expect($matches[0] ?? '')->toContain('hidden');

    $this->putJson(route('student.profile.update'), [
        'first_name' => 'Cecilia',
        'last_name' => 'Student',
        'email' => $user->email,
        'phone' => '+63 912 345 6789',
        'address' => 'Ward II',
        'city' => 'Minglanilla',
        'province' => 'Cebu',
        'zip_code' => '6046',
        'student_number' => 'SCC-19-00012320',
    ])->assertOk()
        ->assertJsonPath('student_id_needs_correction', false);

    $response = $this->get(route('student.profile.edit'))->assertOk();
    preg_match('/<small id="studentIdCorrectionNotice"[^>]*>/', $response->getContent(), $matches);
    expect($matches[0] ?? '')->toContain('hidden');
});

it('lets an administrator view and correct a nonmatching student ID', function () {
    app(AcademicCatalogSeeder::class)->run();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_id_number' => 'MSU-2024-001',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('MSU-2024-001')
        ->assertSee('ID needs correction');

    $this->get(route('admin.students.files', $student))
        ->assertOk()
        ->assertSee('ID needs correction');

    $this->put(route('admin.students.student-id.update', $student), [
        'student_id_number' => ' scc-21-00013240 ',
    ])->assertRedirect()
        ->assertSessionHas('success', 'Student ID updated.');

    expect($student->fresh()->student_id_number)->toBe('SCC-21-00013240');

    $this->get(route('admin.students.files', $student))
        ->assertOk()
        ->assertDontSee('ID needs correction');
});

it('links registered students to the selected catalog program, including legacy names', function () {
    app(AcademicCatalogSeeder::class)->run();
    $institution = Institution::create(['name' => "St. Cecilia's College-Cebu, Inc."]);
    \Illuminate\Support\Facades\Mail::fake();

    $this->post(route('register.store'), [
        'name' => 'Catalog Registration Student',
        'email' => 'catalog-registration@example.test',
        'password' => 'DistinctivePassword#2026',
        'password_confirmation' => 'DistinctivePassword#2026',
        'institution_id' => $institution->id,
        'program' => 'Bachelor of Science in Information Technology',
    ])->assertRedirect(route('verification.notice'));

    $student = Student::whereHas('user', fn ($query) => $query->where('email', 'catalog-registration@example.test'))->firstOrFail();

    expect($student->program_id)->toBe(Program::where('code', 'BSIT')->value('id'))
        ->and($student->program)->toBe('BS Information Technology (BSIT)');
});
