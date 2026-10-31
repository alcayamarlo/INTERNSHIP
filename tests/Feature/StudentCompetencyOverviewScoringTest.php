<?php

use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\StudentCompetencyOverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

function createOverviewStudent(): Student
{
    $user = User::factory()->create(['role' => UserRole::Student]);

    return Student::create(['user_id' => $user->id]);
}

function createOverviewSkill(Student $student, string $name, string $category, string $level, array $extra = []): StudentCompetency
{
    return $student->competencies()->create([
        'name' => $name,
        'category' => $category,
        'proficiency_level' => $level,
        ...$extra,
    ]);
}

it('scores the three configured radar groups from every listed skill, regardless of verification status', function () {
    $student = createOverviewStudent();
    createOverviewSkill($student, 'Technical one', 'technical', 'expert');
    createOverviewSkill($student, 'Skill one', 'skill', 'intermediate');
    createOverviewSkill($student, 'Workshop one', 'workshop', 'advanced');
    createOverviewSkill($student, 'Seminar one', 'seminar', 'expert');
    createOverviewSkill($student, 'Soft one', 'soft', 'advanced');
    createOverviewSkill($student, 'Certificate one', 'certification', 'expert');

    $overview = app(StudentCompetencyOverviewService::class)->forStudent($student);

    expect(array_keys($overview['groups']))->toBe(['technical', 'soft', 'certification'])
        ->and($overview['groups']['technical']['target'])->toBe(6)
        ->and($overview['groups']['technical']['count'])->toBe(4)
        ->and($overview['groups']['technical']['score'])->toBe((100 + 50 + 75 + 100) / 600 * 100)
        ->and($overview['groups']['soft']['score'])->toBe(75 / 4)
        ->and($overview['groups']['certification']['score'])->toBe(50.0)
        ->and($overview['profile_strength']['counted_skill_count'])->toBe(6)
        ->and(collect($overview['skills'])->firstWhere('name', 'Seminar one')['category_label'])->toBe('Seminar');
});

it('returns zero for empty categories and nonpositive configured targets', function () {
    $student = createOverviewStudent();
    config(['competencies.targets.soft' => 0]);

    $overview = app(StudentCompetencyOverviewService::class)->forStudent($student);

    expect($overview['groups']['technical']['score'])->toBe(0.0)
        ->and($overview['groups']['soft']['score'])->toBe(0.0)
        ->and($overview['profile_strength'])->toBe([
            'average_points' => null,
            'average_level' => null,
            'level_label' => 'No counted skills',
            'counted_skill_count' => 0,
        ]);
});

it('caps category percentages at 100 percent', function () {
    $student = createOverviewStudent();
    foreach (range(1, 8) as $number) {
        createOverviewSkill($student, 'Technical expert '.$number, 'technical', 'expert');
    }

    $overview = app(StudentCompetencyOverviewService::class)->forStudent($student);

    expect($overview['groups']['technical']['score'])->toBe(100.0);
});

it('maps profile strength average points to the nearest existing proficiency label', function () {
    $cases = [
        [[25], 'Beginner'],
        [[25, 50], 'Intermediate'],
        [[50, 75], 'Advanced'],
        [[75, 100], 'Expert'],
    ];

    foreach ($cases as $index => [$points, $expectedLabel]) {
        $student = createOverviewStudent();
        foreach ($points as $pointIndex => $point) {
            $level = match ($point) {
                25 => 'beginner',
                50 => 'intermediate',
                75 => 'advanced',
                100 => 'expert',
            };
            createOverviewSkill($student, "Boundary {$index}-{$pointIndex}", 'technical', $level);
        }

        expect(app(StudentCompetencyOverviewService::class)->forStudent($student)['profile_strength']['level_label'])
            ->toBe($expectedLabel);
    }
});

it('uses the configured fallback target when a radar group target is absent', function () {
    $student = createOverviewStudent();
    config([
        'competencies.targets' => ['soft' => 4, 'certification' => 2],
        'competencies.default_target' => 8,
    ]);
    createOverviewSkill($student, 'Technical one', 'technical', 'expert');

    $overview = app(StudentCompetencyOverviewService::class)->forStudent($student);

    expect($overview['groups']['technical']['target'])->toBe(8)
        ->and($overview['groups']['technical']['score'])->toBe(12.5);
});

it('logs unknown category identifiers and includes them in the response without assigning a radar group', function () {
    $student = createOverviewStudent();
    DB::table('student_competencies')->insert([
        'student_id' => $student->id,
        'name' => 'Unmapped item',
        'category' => 'emerging_category',
        'proficiency_level' => 'intermediate',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Log::shouldReceive('warning')
        ->once()
        ->with('Unmapped student competency category in overview scoring.', [
            'student_id' => $student->id,
            'skill_id' => DB::table('student_competencies')->where('name', 'Unmapped item')->value('id'),
            'category' => 'emerging_category',
        ]);

    $overview = app(StudentCompetencyOverviewService::class)->forStudent($student);
    $item = collect($overview['skills'])->firstWhere('name', 'Unmapped item');

    expect($overview['unmapped_categories'])->toBe(['emerging_category'])
        ->and($item['group_key'])->toBeNull()
        ->and($item['category_label'])->toBe('Emerging category')
        ->and($overview['profile_strength']['counted_skill_count'])->toBe(1);
});

it('returns only the requested student competencies and supports the category filter', function () {
    $student = createOverviewStudent();
    $otherStudent = createOverviewStudent();
    createOverviewSkill($student, 'Technical skill', 'technical', 'expert');
    createOverviewSkill($student, 'Soft skill', 'soft', 'beginner');
    createOverviewSkill($student, 'Certification skill', 'certification', 'expert');
    createOverviewSkill($otherStudent, 'Other student', 'technical', 'expert');

    $service = app(StudentCompetencyOverviewService::class);
    $all = $service->forStudent($student);
    $filtered = $service->forStudent($student, 'technical');

    expect(collect($all['skills'])->pluck('name')->all())->toBe(['Certification skill', 'Soft skill', 'Technical skill'])
        ->and($all['profile_strength']['counted_skill_count'])->toBe(3)
        ->and(collect($all['skills'])->pluck('name')->contains('Other student'))->toBeFalse()
        // The skills array always stays complete (the client filters it); only the
        // profile-strength count reflects the category filter.
        ->and(collect($filtered['skills'])->pluck('name')->all())->toBe(['Certification skill', 'Soft skill', 'Technical skill'])
        ->and($filtered['profile_strength']['counted_skill_count'])->toBe(1);
});

it('renders the competency overview without any verification status badge or toggle', function () {
    $student = createOverviewStudent();
    createOverviewSkill($student, 'Cloud certification', 'certification', 'advanced');

    $this->actingAs($student->user)
        ->get(route('student.competencies.index'))
        ->assertOk()
        ->assertSee('My competencies')
        ->assertSee('Technical')
        ->assertSee('Soft skills')
        ->assertSee('Certification')
        ->assertSee('Cloud certification')
        ->assertDontSee('Verified only')
        ->assertDontSee('All submitted')
        ->assertDontSee('co-status-verified', false)
        ->assertDontSee('co-status-pending', false)
        ->assertDontSee('co-status-rejected', false)
        ->assertDontSee('Coordinator note');
});
