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
    $program = Program::create(['name' => 'BS Information Technology', 'code' => 'BSIT', 'coordinator_id' => $coordinator->id, 'required_internship_hours' => 486]);

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

it('lets a coordinator set criteria for their program, saving each edit as a new version', function () {
    [$user, , $program] = criteriaCoordinator();
    $term = currentTermQuery();
    $this->actingAs($user);

    $this->get(route('coordinator.criteria.index'))->assertOk()->assertSee('BSIT')->assertSee('Set criteria');

    $payload = ['school_year' => $term['school_year'], 'term' => $term['term'], 'require_fourth_year' => '1', 'require_major_subjects_passed' => '1', 'max_gwa' => '2.50', 'required_hours' => '500'];
    $this->post(route('coordinator.criteria.store', $program), $payload)->assertRedirect()->assertSessionHas('success', 'Criteria saved as version 1.');
    $this->post(route('coordinator.criteria.store', $program), [...$payload, 'max_gwa' => '2.25', 'notes' => 'Raised for the new term'])
        ->assertSessionHas('success', 'Criteria saved as version 2.');

    $versions = EligibilityCriterion::query()->forTerm($program->id, $term['school_year'], $term['term'])->orderBy('version')->get();
    expect($versions)->toHaveCount(2)
        ->and((float) $versions[0]->max_gwa)->toBe(2.5)
        ->and((float) $versions[1]->max_gwa)->toBe(2.25)
        ->and(app(EligibilityCriteria::class)->latestFor($program->id, $term['school_year'], $term['term'])->version)->toBe(2);

    $this->get(route('coordinator.criteria.show', [$program, ...$term]))
        ->assertOk()
        ->assertSee('Version 2')
        ->assertSee('Version 1')
        ->assertSee('GWA 2.25 or better')
        ->assertSee('Raised for the new term');
    $this->assertDatabaseHas('system_logs', ['action' => 'eligibility_criteria_saved']);
});

it('keeps each term separate', function () {
    [$user, , $program] = criteriaCoordinator();
    $term = currentTermQuery();
    $other = $term['term'] === 'first' ? 'second' : 'first';
    $this->actingAs($user);

    $this->post(route('coordinator.criteria.store', $program), ['school_year' => $term['school_year'], 'term' => $other, 'max_gwa' => '2.00'])->assertRedirect();

    expect(app(EligibilityCriteria::class)->latestFor($program->id, $term['school_year'], $term['term']))->toBeNull()
        ->and(app(EligibilityCriteria::class)->latestFor($program->id, $term['school_year'], $other)->version)->toBe(1);
});

it('does not let a coordinator manage another coordinator\'s program, but admins can', function () {
    [, , $program] = criteriaCoordinator();
    $outsider = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $outsider->id, 'institution_id' => Institution::create(['name' => 'Other'])->id]);
    $term = currentTermQuery();

    $this->actingAs($outsider)->get(route('coordinator.criteria.index'))->assertOk()->assertDontSee('BSIT');
    $this->get(route('coordinator.criteria.show', $program))->assertForbidden();
    $this->post(route('coordinator.criteria.store', $program), [...$term, 'max_gwa' => '1.00'])->assertForbidden();
    expect(EligibilityCriterion::count())->toBe(0);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->get(route('admin.criteria.index'))->assertOk()->assertSee('BSIT');
    $this->post(route('admin.criteria.store', $program), [...$term, 'max_gwa' => '2.75'])->assertRedirect(route('admin.criteria.show', [$program, ...$term]));
    expect(EligibilityCriterion::count())->toBe(1);
});

it('validates the criteria', function () {
    [$user, , $program] = criteriaCoordinator();
    $term = currentTermQuery();

    $this->actingAs($user)
        ->post(route('coordinator.criteria.store', $program), [...$term, 'max_gwa' => '6', 'required_hours' => '0', 'required_document_ids' => [999999], 'term' => 'winter'])
        ->assertSessionHasErrors(['max_gwa' => 'Enter a GWA between 1.00 and 5.00.', 'required_hours', 'required_document_ids.0', 'term']);
    expect(EligibilityCriterion::count())->toBe(0);
});

it('applies the program criteria to eligibility and falls back to the school-wide rules', function () {
    AcademicSetting::current()->update(['require_fourth_year_standing' => true, 'require_major_subjects_passed' => false]);
    [$user, , $program] = criteriaCoordinator();
    $otherProgram = Program::create(['name' => 'BS Accountancy', 'code' => 'BSA', 'required_internship_hours' => 300]);
    $eligibility = app(InternshipEligibility::class);

    // No criteria yet: the global switches apply.
    $thirdYear = criteriaStudent($program, ['year_level' => '3rd Year']);
    expect($eligibility->reasons($thirdYear))->toBe(['You must be in 4th Year to apply.']);

    app(EligibilityCriteria::class)->saveVersion($program, ...array_values(currentTermQuery()), ...[[
        'require_fourth_year' => false, 'require_major_subjects_passed' => false, 'max_gwa' => 2.50, 'required_hours' => 520, 'required_document_ids' => [],
    ], $user]);

    expect($eligibility->reasons($thirdYear->fresh()))->toBe(['Add your GWA on your profile. Your program requires a GWA of 2.50 or better.']);
    $good = criteriaStudent($program, ['year_level' => '3rd Year', 'gwa' => 2.10]);
    $weak = criteriaStudent($program, ['gwa' => 2.90]);
    expect($eligibility->reasons($good))->toBe([])
        ->and($eligibility->reasons($weak))->toBe(['Your program requires a GWA of 2.50 or better.']);

    // Other programs keep the global rule.
    $otherThirdYear = criteriaStudent($otherProgram, ['year_level' => '3rd Year', 'gwa' => 1.50]);
    expect($eligibility->reasons($otherThirdYear))->toBe(['You must be in 4th Year to apply.']);

    // The query version matches (used by Browse Students).
    $ids = $eligibility->constrain(Student::query())->pluck('id')->all();
    expect($ids)->toContain($good->id)->not->toContain($weak->id)->not->toContain($otherThirdYear->id);
});

it('uses the criteria hours for new placements', function () {
    [$user, , $program] = criteriaCoordinator();
    app(EligibilityCriteria::class)->saveVersion($program, ...array_values(currentTermQuery()), ...[[
        'require_fourth_year' => false, 'require_major_subjects_passed' => false, 'max_gwa' => null, 'required_hours' => 520, 'required_document_ids' => [],
    ], $user]);
    $student = criteriaStudent($program);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Acme', 'contact_person' => 'HR', 'verified_at' => now()]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Intern', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 0, 'applied_at' => now()]);

    expect(app(PlacementService::class)->openForApplication($application)->required_hours)->toBe(520);
});

it('offers only student-uploaded, non-on-request documents', function () {
    [$user, , $program] = criteriaCoordinator();
    $student = DeploymentRequirement::create(['code' => 'test-cor', 'name' => 'Test COR', 'is_active' => true, 'on_request' => false, 'upload_source' => DeploymentRequirement::SOURCE_STUDENT, 'sort_order' => 1]);
    $onRequest = DeploymentRequirement::where('on_request', true)->first();

    $this->actingAs($user)->get(route('coordinator.criteria.show', $program))
        ->assertOk()
        ->assertSee('Test COR')
        ->assertDontSee($onRequest?->name ?? 'never-shown');
});
