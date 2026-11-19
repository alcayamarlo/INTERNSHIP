<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/** Fields the student profile form now requires (plus privacy consent); spread before a test's own payload. */
function bioDataDefaults(): array
{
    return [
        'privacy_consent' => '1',
        'gender' => 'Female',
        'date_of_birth' => '2003-05-14',
        'emergency_contact_name' => 'Maria Santos',
        'emergency_contact_relationship' => 'Mother',
        'emergency_contact_address' => '12 Mango Ave, Cebu City',
        'emergency_contact_phone' => '09171234567',
        'department' => 'College of Computer Studies',
        'program' => 'BS Information Technology',
        'year_level' => '3rd Year',
        'expected_graduation' => '2027-05',
        'elementary_school' => 'Cebu Central School',
        'elementary_year_graduated' => '2014-2015',
        'high_school' => 'Cebu City National Science High School',
        'high_school_year_graduated' => '2020-2021',
    ];
}

/**
 * Makes the coordinator the one coordinator of a department (program) and puts the students in it,
 * so the coordinator can see them (coordinators only see their own department).
 */
function assignDepartment(\App\Models\Coordinator $coordinator, \App\Models\Student ...$students): \App\Models\Program
{
    $program = $coordinator->program ?? \App\Models\Program::create([
        'name' => 'Department '.$coordinator->id,
        'code' => 'DEPT'.$coordinator->id,
        'required_internship_hours' => 486,
    ]);
    $coordinator->update(['program_id' => $program->id]);
    foreach ($students as $student) {
        $student->update(['program_id' => $program->id]);
    }

    return $program;
}

/**
 * Paper size (in inches) and page count of a PDF, read from its page objects.
 *
 * @return array{pages: int, width: float, height: float}
 */
function pdfPaper(string $pdf): array
{
    preg_match_all('/\/Type\s*\/Page(?![a-z])/', $pdf, $pages);
    preg_match('/\/MediaBox\s*\[\s*0(?:\.0+)?\s+0(?:\.0+)?\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdf, $box);

    return ['pages' => count($pages[0]), 'width' => round(((float) ($box[1] ?? 0)) / 72, 2), 'height' => round(((float) ($box[2] ?? 0)) / 72, 2)];
}

/** A company (HR account) with a placement and, optionally, supervisors; plus another company's HR account. */
function svCompany(string $name = 'Applus Labs'): array
{
    $hrUser = \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::Employer, 'name' => $name.' HR']);
    $employer = \App\Models\Employer::create(['user_id' => $hrUser->id, 'company_name' => $name, 'contact_person' => $name.' HR', 'verified_at' => now()]);

    return compact('hrUser', 'employer');
}

function svSupervisor(\App\Models\Employer $employer, string $name = 'Sam Supervisor'): \App\Models\User
{
    $user = \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::Supervisor, 'name' => $name]);
    \App\Models\Supervisor::create(['user_id' => $user->id, 'employer_id' => $employer->id, 'position' => 'Senior Engineer']);

    return $user;
}

function svPlacement(\App\Models\Employer $employer, ?\App\Models\User $supervisor = null): \App\Models\Placement
{
    $institution = \App\Models\Institution::firstOrCreate(['name' => 'Supervisor College']);
    $student = \App\Models\Student::create(['user_id' => \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::Student])->id, 'institution_id' => $institution->id, 'program' => 'BSIT']);
    $internship = \App\Models\Internship::create(['employer_id' => $employer->id, 'title' => 'Trainee', 'description' => 'x', 'status' => 'open']);
    $application = \App\Models\InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => \App\Enums\ApplicationStatus::Accepted, 'match_percentage' => 80]);

    return \App\Models\Placement::create([
        'application_id' => $application->id, 'student_id' => $student->id, 'employer_id' => $employer->id, 'internship_id' => $internship->id,
        'start_date' => '2026-02-02', 'required_hours' => 500, 'status' => \App\Models\Placement::STATUS_ACTIVE, 'supervisor_user_id' => $supervisor?->id,
    ]);
}

/**
 * Every value in the personal-data columns, as it sits in the tables, is encrypted: nothing is stored as plain text.
 * Used after the seeders and imports, which must write through the models (a raw insert would skip the encrypted cast).
 */
function expectPersonalDataStoredEncrypted(): void
{
    $checked = 0;
    foreach (\App\Support\SensitiveColumns::COLUMNS as $table => $columns) {
        foreach ($columns as $column) {
            $values = \Illuminate\Support\Facades\DB::table($table)->whereNotNull($column)->pluck($column, 'id');
            $checked += $values->count();
            $plain = $values->reject(fn ($value) => \App\Support\SensitiveColumns::looksEncrypted($value));
            expect($plain->keys()->all())->toBe([], "{$table}.{$column} holds plain-text values (row ids shown)");
        }
    }
    expect($checked)->toBeGreaterThan(0); // the check must not pass on empty tables
}
