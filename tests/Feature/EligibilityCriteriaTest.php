<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\Coordinator;
use App\Models\DeploymentRequirement;
use App\Models\EligibilityCriterion;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\EligibilityCriteria;
use App\Services\InternshipEligibility;
use App\Services\PlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function criteriaCoordinator(): array
{
    $institution = Institution::create(['name' => 'SCC']);
    $user = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create(['user_id' => $user->id, 'institution_id' => $institution->id]);
    $program = Program::create(['name' => 'BS Information Technology', 'code' => 'BSIT', 'required_internship_hours' => 486]);
    $coordinator->update(['program_id' => $program->id]);

    return [$user, $coordinator, $program, $institution];
}

function criteriaStudent(Program $program, array $attributes = []): Student
{
    $user = User::factory()->create(['role' => UserRole::Student]);

    return Student::create(['user_id' => $user->id, 'program_id' => $program->id, 'year_level' => '4th Year', 'major_subjects_passed' => true, ...$attributes]);
}

function currentTermQuery(): array
{
    return app(EligibilityCriteria::class)->currentTerm();
}

it('applies the program criteria to eligibility and falls back to the school-wide rules', function () {
    AcademicSetting::current()->update(['require_fourth_year_standing' => true, 'require_major_subjects_passed' => false]);
    [$user, , $program] = criteriaCoordinator();
    $otherProgram = Program::create(['name' => 'BS Accountancy', 'code' => 'BSA', 'required_internship_hours' => 300]);
    $eligibility = app(InternshipEligibility::class);

    // No criteria yet: the global switches apply.
    $thirdYear = criteriaStudent($program, ['year_level' => '3rd Year']);
    expect($eligibility->reasons($thirdYear))->toBe(['You must be in 4th Year to apply.']);

    app(EligibilityCriteria::class)->saveVersion($program, ...array_values(currentTermQuery()), ...[[
        'require_fourth_year' => false, 'require_major_subjects_passed' => true, 'required_hours' => 520, 'required_document_ids' => [],
    ], $user]);

    expect($eligibility->reasons($thirdYear->fresh()))->toBe([]);
    $good = criteriaStudent($program, ['year_level' => '3rd Year']);
    $weak = criteriaStudent($program, ['major_subjects_passed' => false]);
    expect($eligibility->reasons($good))->toBe([])
        ->and($eligibility->reasons($weak))->toBe(['Your coordinator must confirm that all major subjects are passed.']);

    // Other programs keep the global rule.
    $otherThirdYear = criteriaStudent($otherProgram, ['year_level' => '3rd Year']);
    expect($eligibility->reasons($otherThirdYear))->toBe(['You must be in 4th Year to apply.']);

    // The query version matches (used by Browse Students).
    $ids = $eligibility->constrain(Student::query())->pluck('id')->all();
    expect($ids)->toContain($good->id)->not->toContain($weak->id)->not->toContain($otherThirdYear->id);
});

it('uses the criteria hours for new placements', function () {
    [$user, , $program] = criteriaCoordinator();
    app(EligibilityCriteria::class)->saveVersion($program, ...array_values(currentTermQuery()), ...[[
        'require_fourth_year' => false, 'require_major_subjects_passed' => false, 'required_hours' => 520, 'required_document_ids' => [],
    ], $user]);
    $student = criteriaStudent($program);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Acme', 'contact_person' => 'HR', 'verified_at' => now()]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Intern', 'description' => 'x', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 0, 'applied_at' => now()]);

    expect(app(PlacementService::class)->openForApplication($application)->required_hours)->toBe(520);
});

it('no longer offers Eligibility Criteria to coordinators', function () {
    [$user] = criteriaCoordinator();

    $this->actingAs($user)->get('/coordinator/criteria')->assertNotFound();
    $this->get(route('coordinator.dashboard'))->assertOk()->assertDontSee('Eligibility Criteria');
});
