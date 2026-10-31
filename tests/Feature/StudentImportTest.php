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
    $institution = Institution::create(['name' => 'Test Institution', 'address' => null]);

    $existingUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Old Name']);
    $existing = Student::create([
        'user_id' => $existingUser->id,
        'institution_id' => $institution->id,
        'student_id_number' => 'SCC-26-00000002',
        'student_id_locked' => true,
        'year_level' => '1st Year',
    ]);

    $csv = <<<CSV
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
});
