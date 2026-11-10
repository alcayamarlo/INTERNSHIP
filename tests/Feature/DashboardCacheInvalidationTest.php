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

    // The coordinator dashboard's own keys (per coordinator), not hand-written names.
    $coordinator = \App\Models\Coordinator::create(['user_id' => User::factory()->create(['role' => UserRole::Coordinator])->id, 'institution_id' => $institution->id]);
    $coordinatorKey = fn (string $name) => \App\Services\DashboardCacheInvalidator::coordinatorKey($name, $coordinator->id, $institution->id);

    $keys = [
        'admin_dashboard_stats',
        "student_recent_apps_{$student->id}",
        "employer_all_apps_{$employer->id}",
        "employer_recent_apps_{$employer->id}",
        $coordinatorKey('stats'),
        $coordinatorKey('recent_apps'),
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

    $coordinator = \App\Models\Coordinator::create(['user_id' => User::factory()->create(['role' => UserRole::Coordinator])->id, 'institution_id' => $institution->id]);
    $recentStudentsKey = \App\Services\DashboardCacheInvalidator::coordinatorKey('recent_students', $coordinator->id, $institution->id);

    foreach (['admin_dashboard_stats', 'admin_recent_users', $recentStudentsKey] as $key) {
        Cache::put($key, 'stale', 300);
    }

    $user->update(['name' => 'Updated Student Name']);

    expect(Cache::has('admin_dashboard_stats'))->toBeFalse()
        ->and(Cache::has('admin_recent_users'))->toBeFalse()
        ->and(Cache::has($recentStudentsKey))->toBeFalse()
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
    Cache::put("match_details_{$student->id}_{$internship->id}", 42, 3600);

    $competency = StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Verified PHP',
        'category' => CompetencyCategory::Technical,
        'proficiency_level' => ProficiencyLevel::Advanced,
        'verification_status' => 'verified',
    ]);

    expect(Cache::has("student_comp_score_{$student->id}"))->toBeFalse()
        ->and(Cache::has("recommendations_{$student->id}_5"))->toBeFalse()
        ->and(Cache::has("match_details_{$student->id}_{$internship->id}"))->toBeFalse();

    Cache::put("student_comp_score_{$student->id}", 42, 600);
    $competency->update(['verification_status' => 'rejected']);

    expect(Cache::has("student_comp_score_{$student->id}"))->toBeFalse();
});

it('refreshes the coordinator dashboard caches it actually wrote when a student changes', function () {
    $institution = Institution::create(['name' => 'Coordinator Cache University']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = \App\Models\Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);
    $student = Student::create(['user_id' => User::factory()->create(['role' => UserRole::Student])->id, 'institution_id' => $institution->id]);
    $key = fn (string $name) => \App\Services\DashboardCacheInvalidator::coordinatorKey($name, $coordinator->id, $institution->id);

    $this->actingAs($coordinatorUser)->get(route('coordinator.dashboard'))->assertOk();
    expect(Cache::has($key('stats')))->toBeTrue()
        ->and(Cache::has($key('recent_students')))->toBeTrue();

    $student->update(['program' => 'BS Information Technology']);

    expect(Cache::has($key('stats')))->toBeFalse()
        ->and(Cache::has($key('recent_students')))->toBeFalse()
        ->and(Cache::has($key('recent_apps')))->toBeFalse();
});
