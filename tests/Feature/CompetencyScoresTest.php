<?php

use App\Models\Student;
use App\Models\StudentCompetencyScore;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** A company, its supervisor and an intern with a few scores (a weak one, a strong one). */
function csSetup(): array
{
    $company = svCompany('Scores Co');
    $sam = svSupervisor($company['employer'], 'Sam Supervisor');
    $placement = svPlacement($company['employer'], $sam);
    $student = $placement->student;
    foreach (['Quality of Work' => 91, 'Programming / Technical Competence' => 78, 'Database Management (SQL)' => 66] as $skill => $score) {
        $student->competencyScores()->create(['skill_name' => $skill, 'score' => $score]);
    }

    return [...$company, 'sam' => $sam, 'placement' => $placement, 'student' => $student];
}

it('lists a student\'s scores in the school\'s order, clamped to 0-100', function () {
    $f = csSetup();
    $f['student']->competencyScores()->create(['skill_name' => 'Something Extra', 'score' => 250]);

    $list = StudentCompetencyScore::forDisplay($f['student']->fresh()->competencyScores);

    // Programming, SQL, Quality of Work are in the fixed order; unknown skills come last; scores stay 0-100.
    expect(array_column($list, 'skill'))->toBe(['Programming / Technical Competence', 'Database Management (SQL)', 'Quality of Work', 'Something Extra'])
        ->and(array_column($list, 'score'))->toBe([78, 66, 91, 100])
        ->and(array_keys(StudentCompetencyScore::SKILLS))->toHaveCount(10);
});

it('shows the scores as progress bars on the student\'s own profile, read-only', function () {
    $f = csSetup();

    $this->actingAs($f['student']->user)->get(route('student.profile.edit'))->assertOk()
        ->assertSee('Competency scores')->assertSee('Average 78%')
        ->assertSeeInOrder(['Programming / Technical Competence', '78%', 'Database Management (SQL)', '66%', 'Quality of Work', '91%'])
        ->assertSee('role="progressbar"', false)->assertSee('width: 91%', false)
        ->assertDontSee('name="competency_score', false);
});

it('shows the scores to the company on the applicant page and to the supervisor and HR on the intern page', function () {
    $f = csSetup();

    $this->actingAs($f['hrUser'])->get(route('employer.applicants.show', $f['placement']->application))->assertOk()
        ->assertSee('Competency scores')->assertSee('Quality of Work')->assertSee('91%');
    $this->actingAs($f['sam'])->get(route('supervisor.interns.show', $f['placement']))->assertOk()
        ->assertSee('Competency scores of this intern')->assertSee('Database Management (SQL)')->assertSee('66%');
    $this->actingAs($f['hrUser'])->get(route('employer.interns.show', $f['placement']))->assertOk()->assertSee('78%');
});

it('shows nothing when a student has no scores, and removes the scores with the student', function () {
    $f = csSetup();
    $other = svPlacement($f['employer'], $f['sam']);

    $this->actingAs($f['sam'])->get(route('supervisor.interns.show', $other))->assertOk()->assertDontSee('Competency scores');

    $studentId = $f['student']->id;
    $f['student']->forceDelete();
    $this->assertDatabaseMissing('student_competency_scores', ['student_id' => $studentId]);
    expect(Student::find($studentId))->toBeNull();
});
