<?php

use App\Enums\UserRole;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

function studentImportCsv(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('students.csv', $content);
}

it('blocks non-admins from the import form', function () {
    $employer = User::factory()->create(['role' => UserRole::Employer]);

    $this->actingAs($employer)
        ->get(route('admin.students.import.create'))
        ->assertForbidden();
});

it('imports valid rows, updates existing students by school ID, and reports invalid rows without aborting the batch', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $institution = Institution::create(['name' => 'Test Institution', 'address' => null, 'verified_at' => now()]);

    $existingUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Old Name']);
    $existing = Student::create([
        'user_id' => $existingUser->id,
        'institution_id' => $institution->id,
        'student_id_number' => 'SCC-26-00000002',
        'student_id_locked' => true,
        'year_level' => '1st Year',
    ]);

    $csv = <<<'CSV'
    school_id,last_name,first_name,middle_name,date_of_birth,program,year_level,section
    SCC-26-00000001,Dela Cruz,Juan,Santos,2003-05-14,,3rd Year,A
    SCC-26-00000002,Reyes,Maria,,2002-01-01,,2nd Year,B
    BAD-ID,Santos,Pedro,,2001-03-03,,1st Year,A
    SCC-26-00000003,Lopez,Ana,,not-a-date,,4th Year,C
    CSV;

    $response = $this->actingAs($admin)->post(route('admin.students.import.store'), [
        'institution_id' => $institution->id,
        'file' => studentImportCsv($csv),
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('students', [
        'student_id_number' => 'SCC-26-00000001',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);
    $this->assertDatabaseCount('students', 2); // the pre-existing one, updated in place + the newly created one

    $createdUser = Student::where('student_id_number', 'SCC-26-00000001')->firstOrFail()->user;
    expect($createdUser->must_change_password)->toBeTrue();
    expect($createdUser->email)->toBeNull();
    expect(Hash::check('05142003', $createdUser->password))->toBeTrue();

    $existing->refresh();
    expect($existing->first_name)->toBe('Maria')
        ->and($existing->last_name)->toBe('Reyes')
        ->and($existing->year_level)->toBe('2nd Year');
    // Re-importing an existing student must not touch their login credentials.
    expect($existingUser->fresh()->password)->toBe($existingUser->password)
        ->and($existingUser->fresh()->must_change_password)->toBeFalse();

    $response->assertViewHas('summary', fn ($summary) => $summary['created'] === 1
        && $summary['updated'] === 1
        && $summary['error'] === 2);

    // The import writes through the models: the birth dates it saved are encrypted in the table, and read back correctly.
    expectPersonalDataStoredEncrypted();
    expect(Student::where('student_id_number', 'SCC-26-00000001')->firstOrFail()->date_of_birth->toDateString())->toBe('2003-05-14');
});

it('stores registrar semester names the way the profile form does and rejects unreadable ones', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $institution = Institution::create(['name' => 'Semester Institution', 'address' => null, 'verified_at' => now()]);

    // "1st Semester" used to be saved as typed, which MariaDB refused (the column holds first/second/summer).
    $csv = <<<'CSV'
    school_id,last_name,first_name,middle_name,date_of_birth,program,year_level,section,school_year,semester
    SCC-26-00000011,Uno,Ana,,2003-05-14,,4th Year,A,2026-2027,1st Semester
    SCC-26-00000012,Dos,Ben,,2003-05-14,,4th Year,A,2026 - 2027,2nd sem
    SCC-26-00000013,Tres,Cy,,2003-05-14,,4th Year,A,,Summer
    SCC-26-00000014,Cuatro,Di,,2003-05-14,,4th Year,A,2026-2027,Third
    SCC-26-00000015,Cinco,Ed,,2003-05-14,,4th Year,A,26-27,1st Semester
    CSV;

    $this->actingAs($admin)->post(route('admin.students.import.store'), [
        'institution_id' => $institution->id,
        'file' => studentImportCsv($csv),
    ])->assertOk()
        ->assertSee('Semester could not be read (use 1st Semester, 2nd Semester or Summer).')
        ->assertSee('School year must look like 2026-2027.')
        ->assertDontSee('SQLSTATE');

    expect(Student::where('student_id_number', 'SCC-26-00000011')->value('semester'))->toBe('first')
        ->and(Student::where('student_id_number', 'SCC-26-00000012')->first()->only(['semester', 'school_year']))->toBe(['semester' => 'second', 'school_year' => '2026-2027'])
        ->and(Student::where('student_id_number', 'SCC-26-00000013')->value('semester'))->toBe('summer')
        ->and(Student::whereIn('student_id_number', ['SCC-26-00000014', 'SCC-26-00000015'])->exists())->toBeFalse();
});
