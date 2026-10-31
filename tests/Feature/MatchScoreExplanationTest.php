<?php

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipRequirement;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use App\Services\CompetencyMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function matchExplanationFixture(): array
{
    $institution = Institution::create(['name' => 'Metro State University', 'address' => '123 Education Ave', 'contact_email' => 'info@msu.edu.ph']);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Match Labs', 'contact_person' => 'Match Employer']);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id, 'institution_id' => $institution->id, 'student_id_number' => 'SCC-25-00000099', 'program' => 'BS Computer Science']);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Explainable Match Intern', 'description' => 'Test posting.', 'status' => 'open']);

    InternshipRequirement::create(['internship_id' => $internship->id, 'requirement_name' => 'No Evidence Skill', 'required_level' => ProficiencyLevel::Intermediate]);
    InternshipRequirement::create(['internship_id' => $internship->id, 'requirement_name' => 'Unverified Skill', 'required_level' => ProficiencyLevel::Intermediate]);
    InternshipRequirement::create(['internship_id' => $internship->id, 'requirement_name' => 'Low Level Skill', 'required_level' => ProficiencyLevel::Expert]);
    InternshipRequirement::create(['internship_id' => $internship->id, 'requirement_name' => 'Matched Skill', 'required_level' => ProficiencyLevel::Intermediate]);

    StudentCompetency::create(['student_id' => $student->id, 'name' => 'Unverified Skill', 'category' => CompetencyCategory::Technical, 'proficiency_level' => ProficiencyLevel::Advanced]);
    StudentCompetency::create(['student_id' => $student->id, 'name' => 'Low Level Skill', 'category' => CompetencyCategory::Technical, 'proficiency_level' => ProficiencyLevel::Beginner]);
    StudentCompetency::create(['student_id' => $student->id, 'name' => 'Matched Skill', 'category' => CompetencyCategory::Technical, 'proficiency_level' => ProficiencyLevel::Advanced]);

    return compact('studentUser', 'student', 'internship');
}

it('explains each missing requirement with its specific reason', function () {
    $fixture = matchExplanationFixture();

    $result = app(CompetencyMatchingService::class)->calculateMatchWithDetails($fixture['student'], $fixture['internship']);
    $missing = collect($result['details']['missing_requirements'])->keyBy('name');
    $matched = collect($result['details']['matched_requirements'])->keyBy('name');

    expect($matched->has('Matched Skill'))->toBeTrue()
        ->and($matched->has('Unverified Skill'))->toBeTrue()
        ->and($missing->get('No Evidence Skill')['reason'])->toBe('no_evidence')
        ->and($missing->get('Low Level Skill')['reason'])->toBe('below_required_level')
        ->and($result['details']['matched_count'])->toBe(2);
});

it('shows the why-this-score breakdown on the internship details page', function () {
    $fixture = matchExplanationFixture();

    $this->actingAs($fixture['studentUser'])
        ->get(route('student.internships.show', $fixture['internship']))
        ->assertOk()
        ->assertSee('Why this score')
        ->assertSee('Matched Skill')
        ->assertSee('Unverified Skill')
        ->assertSee('No evidence added for this skill yet.')
        ->assertSee('below the required Expert level');
});

it('invalidates the cached match details when a student updates their competencies', function () {
    $fixture = matchExplanationFixture();
    $service = app(CompetencyMatchingService::class);

    $before = $service->calculateMatchWithDetails($fixture['student'], $fixture['internship']);
    expect($before['details']['matched_count'])->toBe(2);

    StudentCompetency::create(['student_id' => $fixture['student']->id, 'name' => 'No Evidence Skill', 'category' => CompetencyCategory::Technical, 'proficiency_level' => ProficiencyLevel::Advanced]);
    $service->invalidateCache($fixture['student']);

    $after = $service->calculateMatchWithDetails($fixture['student']->fresh(), $fixture['internship']);
    expect($after['details']['matched_count'])->toBe(3);
});
