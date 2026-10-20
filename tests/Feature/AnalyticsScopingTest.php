<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function analyticsScopeFixture(string $institutionName): array
{
    $institution = Institution::create(['name' => $institutionName, 'address' => '123 Ave', 'contact_email' => strtolower(str_replace(' ', '', $institutionName)).'@example.edu']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);

    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => $institutionName.' Co', 'contact_person' => 'Employer']);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id, 'institution_id' => $institution->id]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => $institutionName.' Intern', 'description' => 'Test.', 'status' => 'open']);
    InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => ApplicationStatus::Submitted,
        'match_percentage' => 70,
        'applied_at' => now(),
    ]);

    return compact('coordinatorUser', 'student');
}

it('restricts the analytics endpoint to admins and coordinators', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);
    $employer = User::factory()->create(['role' => UserRole::Employer]);

    $this->actingAs($student)->getJson(route('analytics.charts'))->assertForbidden();
    $this->actingAs($employer)->getJson(route('analytics.charts'))->assertForbidden();
});

it('scopes a coordinator analytics view to their own institution', function () {
    $a = analyticsScopeFixture('Alpha University');
    $b = analyticsScopeFixture('Beta University');

    $response = $this->actingAs($a['coordinatorUser'])
        ->getJson(route('analytics.charts'))
        ->assertOk()
        ->assertJsonPath('scoped', true)
        ->assertJsonPath('summary.total_applications', 1);

    $response->assertOk();

    $this->actingAs($a['coordinatorUser'])
        ->get(route('coordinator.analytics'))
        ->assertOk()
        ->assertSee('Placement Rate Trend');
});

it('gives admins an unscoped, platform-wide view', function () {
    analyticsScopeFixture('Alpha University');
    analyticsScopeFixture('Beta University');

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->getJson(route('analytics.charts'))
        ->assertOk()
        ->assertJsonPath('scoped', false)
        ->assertJsonPath('summary.total_applications', 2);
});

it('accepts a months parameter capped between 1 and 24', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->getJson(route('analytics.charts', ['months' => 48]))
        ->assertOk()->assertJsonPath('months', 24);
    $this->actingAs($admin)->getJson(route('analytics.charts', ['months' => 0]))
        ->assertOk()->assertJsonPath('months', 1);
});
