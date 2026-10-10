<?php

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('serves private portfolio files only to their owning student', function () {
    Storage::fake('private');
    Storage::fake('public');

    $owner = User::factory()->create([
        'role' => UserRole::Student,
        'email_verified_at' => now(),
    ]);
    $ownerStudent = Student::create([
        'user_id' => $owner->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $otherStudentUser = User::factory()->create([
        'role' => UserRole::Student,
        'email_verified_at' => now(),
    ]);
    Student::create([
        'user_id' => $otherStudentUser->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);

    $path = 'portfolios/'.$ownerStudent->id.'/private.pdf';
    Storage::disk('private')->put($path, 'private evidence');
    $portfolio = Portfolio::create([
        'student_id' => $ownerStudent->id,
        'title' => 'Private Evidence',
        'type' => 'project',
        'file_path' => $path,
    ]);
    $certificatePath = 'certificates/'.$ownerStudent->id.'/certificate.pdf';
    Storage::disk('private')->put($certificatePath, 'private certificate');
    $certificate = Certificate::create([
        'student_id' => $ownerStudent->id,
        'title' => 'Private Certificate',
        'file_path' => $certificatePath,
    ]);

    $this->actingAs($owner)
        ->get(route('student.portfolio.download', $portfolio))
        ->assertOk();

    $this->actingAs($otherStudentUser)
        ->get(route('student.portfolio.download', $portfolio))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('student.certificates.download', $certificate))
        ->assertOk();

    $this->actingAs($otherStudentUser)
        ->get(route('student.certificates.download', $certificate))
        ->assertForbidden();

    expect(Storage::disk('public')->exists($path))->toBeFalse();
});

it('denies a different student direct access to a private resume', function () {
    Storage::fake('private');

    $owner = User::factory()->create(['role' => UserRole::Student]);
    $ownerStudent = Student::create([
        'user_id' => $owner->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $otherStudent = User::factory()->create(['role' => UserRole::Student]);
    Student::create([
        'user_id' => $otherStudent->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);

    $path = 'resumes/'.$ownerStudent->id.'/resume.pdf';
    Storage::disk('private')->put($path, 'private resume');
    $resume = Resume::create([
        'student_id' => $ownerStudent->id,
        'file_path' => $path,
        'generated_at' => now(),
    ]);

    $this->actingAs($otherStudent)
        ->get(route('student.resume.download', $resume))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('student.resume.download', $resume))
        ->assertOk();
});

it('copies legacy files before removing public copies only when requested', function () {
    Storage::fake('private');
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $user->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $path = 'portfolios/'.$student->id.'/legacy.pdf';
    Storage::disk('public')->put($path, 'legacy evidence');
    Storage::disk('private')->put($path, 'broken evidence');
    Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Legacy Evidence',
        'type' => 'project',
        'file_path' => $path,
    ]);

    Artisan::call('files:secure-existing');

    expect(Storage::disk('private')->get($path))->toBe('legacy evidence')
        ->and(Storage::disk('public')->exists($path))->toBeTrue();

    Artisan::call('files:secure-existing', ['--remove-public' => true]);

    expect(Storage::disk('private')->exists($path))->toBeTrue()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('allows an employer to download only portfolios belonging to its applicant', function () {
    Storage::fake('private');

    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Applicant Portfolio Company',
        'industry' => 'Technology',
        'contact_person' => 'Employer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Software Intern',
        'description' => 'Build software.',
        'work_setup' => 'hybrid',
        'status' => 'open',
    ]);
    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'match_percentage' => 50,
        'applied_at' => now(),
    ]);
    $path = 'portfolios/'.$student->id.'/applicant.pdf';
    Storage::disk('private')->put($path, 'applicant portfolio');
    $portfolio = Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Applicant Portfolio',
        'type' => 'project',
        'file_path' => $path,
    ]);

    $this->actingAs($employerUser)
        ->get(route('employer.applicants.portfolios.download', [$application, $portfolio]))
        ->assertOk();

    $otherEmployer = User::factory()->create(['role' => UserRole::Employer]);
    Employer::create([
        'user_id' => $otherEmployer->id,
        'company_name' => 'Other Portfolio Company',
        'contact_person' => 'Other Employer',
    ]);

    $this->actingAs($otherEmployer)
        ->get(route('employer.applicants.portfolios.download', [$application, $portfolio]))
        ->assertForbidden();
});
