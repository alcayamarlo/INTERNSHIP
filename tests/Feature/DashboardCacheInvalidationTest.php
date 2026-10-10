<?php

use App\Enums\ApplicationStatus;
use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('invalidates dashboard caches when an application is created or updated', function () {
    $institution = Institution::create(['name' => 'Cache Test University']);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => $institution->id,
        'profile_completion' => 0,
    ]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Cache Test Employer',
        'contact_person' => 'Employer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Cache Test Internship',
        'description' => 'A test internship.',
        'work_setup' => 'hybrid',
        'status' => 'open',
    ]);

    $keys = [
        'admin_dashboard_stats',
        "student_recent_apps_{$student->id}",
        "employer_all_apps_{$employer->id}",
        "employer_recent_apps_{$employer->id}",
        "coordinator_stats_{$institution->id}",
        "coordinator_recent_apps_{$institution->id}",
    ];
    foreach ($keys as $key) {
        Cache::put($key, 'stale', 300);
    }

    $application = InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => ApplicationStatus::Submitted,
        'match_percentage' => 50,
        'applied_at' => now(),
    ]);

    foreach ($keys as $key) {
        expect(Cache::has($key))->toBeFalse();
    }

    Cache::put('admin_dashboard_stats', 'stale', 300);
    Cache::put("employer_all_apps_{$employer->id}", 'stale', 300);
    $application->update(['status' => ApplicationStatus::Reviewed]);

    expect(Cache::has('admin_dashboard_stats'))->toBeFalse()
        ->and(Cache::has("employer_all_apps_{$employer->id}"))->toBeFalse();
});

it('invalidates admin and coordinator user caches when a student account changes', function () {
    $institution = Institution::create(['name' => 'User Cache University']);
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'profile_completion' => 0,
    ]);

    foreach (['admin_dashboard_stats', 'admin_recent_users', "coordinator_recent_students_{$institution->id}"] as $key) {
        Cache::put($key, 'stale', 300);
    }

    $user->update(['name' => 'Updated Student Name']);

    expect(Cache::has('admin_dashboard_stats'))->toBeFalse()
        ->and(Cache::has('admin_recent_users'))->toBeFalse()
        ->and(Cache::has("coordinator_recent_students_{$institution->id}"))->toBeFalse()
        ->and($student->fresh()->institution_id)->toBe($institution->id);
});

it('invalidates competency score and recommendation caches when evidence changes', function () {
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $user->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Evidence Cache Employer',
        'contact_person' => 'Employer',
    ]);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Evidence Cache Internship',
        'description' => 'A test internship.',
        'work_setup' => 'hybrid',
        'status' => 'open',
    ]);

    Cache::put("student_comp_score_{$student->id}", 42, 600);
    Cache::put("recommendations_{$student->id}_5", collect(), 1800);
    Cache::put("match_{$student->id}_{$internship->id}", 42, 3600);

    $competency = StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Verified PHP',
        'category' => CompetencyCategory::Technical,
        'proficiency_level' => ProficiencyLevel::Advanced,
        'verification_status' => 'verified',
    ]);

    expect(Cache::has("student_comp_score_{$student->id}"))->toBeFalse()
        ->and(Cache::has("recommendations_{$student->id}_5"))->toBeFalse()
        ->and(Cache::has("match_{$student->id}_{$internship->id}"))->toBeFalse();

    Cache::put("student_comp_score_{$student->id}", 42, 600);
    $competency->update(['verification_status' => 'rejected']);

    expect(Cache::has("student_comp_score_{$student->id}"))->toBeFalse();
});
