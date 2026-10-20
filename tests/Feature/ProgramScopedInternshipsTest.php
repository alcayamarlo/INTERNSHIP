<?php

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function programScopeFixture(): array
{
    $it = Program::firstOrCreate(['code' => 'BSIT'], ['name' => 'BS Information Technology', 'required_internship_hours' => 486]);
    $hm = Program::firstOrCreate(['code' => 'BSHM'], ['name' => 'BS Hospitality Management', 'required_internship_hours' => 600]);

    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Scope Co', 'contact_person' => 'Scope']);

    $make = function (string $title, array $programs, ?string $skill = null) use ($employer) {
        $internship = Internship::create(['employer_id' => $employer->id, 'title' => $title, 'description' => 'Test posting.', 'status' => 'open']);
        $internship->programs()->sync(collect($programs)->pluck('id'));
        if ($skill) {
            $internship->requirementsList()->create(['requirement_name' => $skill, 'required_level' => ProficiencyLevel::Beginner]);
        }

        return $internship;
    };

    $itMatch = $make('IT Matched Role', [$it], 'Laravel Framework');
    $itOther = $make('IT Unmatched Role', [$it], 'Cobol');
    $hotel = $make('Hotel Front Desk', [$hm]);
    $open = $make('Open To Everyone', []);

    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $studentUser->id, 'program_id' => $it->id, 'program' => 'BS Information Technology (BSIT)']);
    StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Laravel Framework',
        'category' => CompetencyCategory::Technical,
        'proficiency_level' => ProficiencyLevel::Advanced,
        'verification_status' => 'verified',
    ]);

    return compact('studentUser', 'employerUser', 'it', 'hm', 'itMatch', 'itOther', 'hotel', 'open');
}

it('shows only the student program by default, best competency match first', function () {
    $f = programScopeFixture();

    $response = $this->actingAs($f['studentUser'])->get(route('student.internships.index'))
        ->assertOk()
        ->assertSee('data-internship-scope="program"', false)
        ->assertSee('IT Matched Role')
        ->assertSee('IT Unmatched Role')
        ->assertSee('Open To Everyone')
        ->assertDontSee('Hotel Front Desk');

    $response->assertSeeInOrder(['IT Matched Role', 'IT Unmatched Role']);
});

it('searches across every program even without a competency match', function () {
    $f = programScopeFixture();

    $this->actingAs($f['studentUser'])->get(route('student.internships.index', ['search' => 'Hotel']))
        ->assertOk()
        ->assertSee('data-internship-scope="all"', false)
        ->assertSee('Hotel Front Desk')
        ->assertSee('0% match');

    $this->actingAs($f['studentUser'])->get(route('student.internships.index', ['scope' => 'all']))
        ->assertOk()
        ->assertSee('Hotel Front Desk');

    // The student can explicitly narrow a search back to their program.
    $this->actingAs($f['studentUser'])->get(route('student.internships.index', ['search' => 'Hotel', 'scope' => 'program']))
        ->assertOk()
        ->assertDontSee('Hotel Front Desk');
});

it('keeps recommendations within the student program', function () {
    $f = programScopeFixture();

    $recommendations = app(\App\Services\CompetencyMatchingService::class)
        ->getRecommendations($f['studentUser']->student, 10)
        ->pluck('title');

    expect($recommendations)->toContain('IT Matched Role')->not->toContain('Hotel Front Desk');
});

it('uses the student program required hours for a new placement', function () {
    $f = programScopeFixture();
    $application = \App\Models\InternshipApplication::create([
        'internship_id' => $f['itMatch']->id,
        'student_id' => $f['studentUser']->student->id,
        'status' => \App\Enums\ApplicationStatus::Interview,
        'match_percentage' => 90,
    ]);

    $this->actingAs($f['employerUser'])
        ->put(route('employer.applicants.status', $application), ['status' => 'accepted'])
        ->assertSessionHas('success');

    expect($application->placement()->first()->required_hours)->toBe($f['it']->required_internship_hours);
});

it('lets employers choose which programs an internship is open to', function () {
    $f = programScopeFixture();

    $this->actingAs($f['employerUser'])->post(route('employer.internships.store'), [
        'title' => 'Guest Relations Intern',
        'description' => 'Hospitality role.',
        'work_setup' => 'onsite',
        'status' => 'open',
        'program_ids' => [$f['hm']->id],
    ])->assertRedirect(route('employer.internships.index'));

    $internship = Internship::firstWhere('title', 'Guest Relations Intern');
    expect($internship->programs->pluck('code')->all())->toBe(['BSHM']);

    $this->actingAs($f['employerUser'])->get(route('employer.internships.edit', $internship))
        ->assertOk()
        ->assertSee('Open to programs');
});
