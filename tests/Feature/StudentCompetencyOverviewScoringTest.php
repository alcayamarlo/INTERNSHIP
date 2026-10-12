<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use App\Services\StudentCompetencyOverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function createOverviewStudent(): Student
{
    $user = User::factory()->create(['role' => UserRole::Student]);

    return Student::create(['user_id' => $user->id]);
}

function createOverviewSkill(Student $student, string $name, string $category, string $level, string $status, array $extra = []): StudentCompetency
{
    return $student->competencies()->create([
        'name' => $name,
        'category' => $category,
        'proficiency_level' => $level,
        'verification_status' => $status,
        ...$extra,
    ]);
}

it('scores the three configured radar groups and excludes rejected and unknown statuses', function () {
    $student = createOverviewStudent();
    createOverviewSkill($student, 'Verified technical', 'technical', 'expert', 'verified');
    createOverviewSkill($student, 'Pending skill', 'skill', 'intermediate', 'evidence_submitted');
    createOverviewSkill($student, 'Approved workshop', 'workshop', 'advanced', 'approved');
    createOverviewSkill($student, 'Rejected seminar', 'seminar', 'expert', 'rejected');
    createOverviewSkill($student, 'Draft training', 'training', 'expert', 'draft');
    createOverviewSkill($student, 'Verified soft', 'soft', 'advanced', 'verified');
    createOverviewSkill($student, 'Verified certificate', 'certification', 'expert', 'verified');

    $overview = app(StudentCompetencyOverviewService::class)->forStudent($student, 'all');

    expect(array_keys($overview['groups']))->toBe(['technical', 'soft', 'certification'])
        ->and($overview['groups']['technical']['target'])->toBe(6)
        ->and($overview['groups']['technical']['scores']['verified'])->toBe(175 / 600 * 100)
        ->and($overview['groups']['technical']['scores']['all_submitted'])->toBe(225 / 600 * 100)
        ->and($overview['groups']['technical']['counts'])->toBe([
            'verified' => 2,
            'all_submitted' => 3,
            'pending' => 1,
            'rejected' => 1,
            'unmapped' => 1,
        ])
        ->and($overview['groups']['soft']['scores']['verified'])->toBe(75 / 4)
        ->and($overview['groups']['certification']['scores']['verified'])->toBe(50.0)
        ->and($overview['profile_strength']['counted_skill_count'])->toBe(5)
        ->and($overview['profile_strength']['average_points'])->toBe(80.0)
        ->and($overview['profile_strength']['level_label'])->toBe('Advanced')
        ->and($overview['unmapped']['count'])->toBe(1)
        ->and($overview['unmapped']['statuses'])->toBe(['draft'])
        ->and(collect($overview['skills'])->firstWhere('name', 'Rejected seminar')['category_label'])->toBe('Seminar');
});

it('returns zero for empty categories and nonpositive configured targets', function () {
    $student = createOverviewStudent();
    config(['competencies.targets.soft' => 0]);

    $overview = app(StudentCompetencyOverviewService::class)->forStudent($student);

    expect($overview['groups']['technical']['scores'])->toBe(['verified' => 0.0, 'all_submitted' => 0.0])
        ->and($overview['groups']['soft']['scores'])->toBe(['verified' => 0.0, 'all_submitted' => 0.0])
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
        createOverviewSkill($student, 'Technical expert '.$number, 'technical', 'expert', 'verified');
    }

    $overview = app(StudentCompetencyOverviewService::class)->forStudent($student);

    expect($overview['groups']['technical']['scores']['verified'])->toBe(100.0);
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
            createOverviewSkill($student, "Boundary {$index}-{$pointIndex}", 'technical', $level, 'verified');
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
    createOverviewSkill($student, 'Verified technical', 'technical', 'expert', 'verified');

    $overview = app(StudentCompetencyOverviewService::class)->forStudent($student);

    expect($overview['groups']['technical']['target'])->toBe(8)
        ->and($overview['groups']['technical']['scores']['verified'])->toBe(12.5);
});

it('logs unknown category identifiers and includes them in the response without assigning a radar group', function () {
    $student = createOverviewStudent();
    DB::table('student_competencies')->insert([
        'student_id' => $student->id,
        'name' => 'Unmapped item',
        'category' => 'emerging_category',
        'proficiency_level' => 'intermediate',
        'verification_status' => 'verified',
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

it('logs unknown status values with skill IDs and reports their unmapped count', function () {
    $student = createOverviewStudent();
    $draft = createOverviewSkill($student, 'Draft item', 'technical', 'intermediate', 'draft');
    $other = createOverviewSkill($student, 'Other status item', 'technical', 'advanced', 'withdrawn');
    Log::shouldReceive('warning')
        ->twice()
        ->with('Unmapped student competency status in overview scoring.', \Mockery::on(
            fn (array $context) => $context['student_id'] === $student->id
                && in_array($context['skill_id'], [$draft->id, $other->id], true)
                && in_array($context['status'], ['draft', 'withdrawn'], true)
        ));

    $overview = app(StudentCompetencyOverviewService::class)->forStudent($student, 'all');

    expect($overview['unmapped']['count'])->toBe(2)
        ->and($overview['unmapped']['statuses'])->toBe(['draft', 'withdrawn'])
        ->and($overview['profile_strength']['counted_skill_count'])->toBe(0)
        ->and(collect($overview['skills'])->every(fn (array $skill) => ! $skill['counts_in_score']))->toBeTrue();
});

it('returns only the requested student competencies and supports the view and category filters', function () {
    $student = createOverviewStudent();
    $otherStudent = createOverviewStudent();
    createOverviewSkill($student, 'Verified', 'technical', 'expert', 'verified');
    createOverviewSkill($student, 'Pending', 'soft', 'beginner', 'evidence_submitted');
    createOverviewSkill($student, 'Rejected', 'certification', 'expert', 'rejected');
    createOverviewSkill($otherStudent, 'Other student', 'technical', 'expert', 'verified');

    $service = app(StudentCompetencyOverviewService::class);
    $verified = $service->forStudent($student, 'verified', 'technical');
    $all = $service->forStudent($student, 'all');

    expect(collect($verified['skills'])->pluck('name')->all())->toBe(['Pending', 'Rejected', 'Verified'])
        ->and($verified['profile_strength']['counted_skill_count'])->toBe(1)
        ->and($verified['profile_strength']['average_points'])->toBe(100.0)
        ->and($all['profile_strength']['counted_skill_count'])->toBe(2)
        ->and(collect($all['skills'])->pluck('name')->contains('Other student'))->toBeFalse();
});

it('reflects a coordinator review update from the same student competency row on the next service call', function () {
    $institution = Institution::create(['name' => 'St. Cecilia College']);
    $student = createOverviewStudent();
    $student->update(['institution_id' => $institution->id]);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create([
        'user_id' => $coordinatorUser->id,
        'institution_id' => $institution->id,
    ]);
    $skill = createOverviewSkill($student, 'Reviewed technical skill', 'technical', 'intermediate', 'evidence_submitted');
    $service = app(StudentCompetencyOverviewService::class);

    expect($service->forStudent($student, 'verified')['groups']['technical']['counts']['verified'])->toBe(0)
        ->and($service->forStudent($student, 'all')['groups']['technical']['scores']['all_submitted'])->toBe(50 / 600 * 100);

    $this->actingAs($coordinatorUser)
        ->post(route('coordinator.verification.reviewCompetency', $skill), [
            'verification_status' => 'verified',
            'review_notes' => 'Evidence checked.',
        ])
        ->assertRedirect();

    expect($service->forStudent($student->fresh(), 'verified')['groups']['technical']['counts']['verified'])->toBe(1)
        ->and($service->forStudent($student->fresh(), 'all')['groups']['technical']['counts']['pending'])->toBe(0)
        ->and($skill->fresh()->verification_status)->toBe('verified')
        ->and($skill->fresh()->proficiency_level->value)->toBe('intermediate');
});

it('resubmits a rejected skill as evidence_submitted when replacement evidence is uploaded', function () {
    Storage::fake('private');
    $student = createOverviewStudent();
    $skill = createOverviewSkill($student, 'Rejected skill', 'technical', 'beginner', 'rejected', [
        'assessment_name' => 'Initial assessment',
        'review_notes' => 'Please provide clearer evidence.',
    ]);

    $this->actingAs($student->user)
        ->put(route('student.competencies.update', $skill), [
            'name' => 'Rejected skill',
            'category' => 'technical',
            'proficiency_level' => 'advanced',
            'assessment_name' => 'Updated assessment',
            'evidence' => UploadedFile::fake()->createWithContent('replacement.pdf', "%PDF-1.4\nreplacement evidence"),
        ])
        ->assertRedirect(route('student.competencies.index'));

    expect($skill->fresh()->verification_status)->toBe('evidence_submitted')
        ->and($skill->fresh()->proficiency_level->value)->toBe('advanced')
        ->and(app(StudentCompetencyOverviewService::class)->forStudent($student->fresh(), 'verified')['groups']['technical']['counts']['verified'])->toBe(0)
        ->and(app(StudentCompetencyOverviewService::class)->forStudent($student->fresh(), 'all')['groups']['technical']['counts']['pending'])->toBe(1);
});

it('renders the competency overview and shows coordinator notes for rejected skills', function () {
    $student = createOverviewStudent();
    createOverviewSkill($student, 'Rejected cloud certification', 'certification', 'advanced', 'rejected', [
        'review_notes' => 'Please add the signed certificate.',
    ]);

    $this->actingAs($student->user)
        ->get(route('student.competencies.index', ['view' => 'all']))
        ->assertOk()
        ->assertSee('My competencies')
        ->assertSee('Verified only')
        ->assertSee('All submitted')
        ->assertSee('Technical')
        ->assertSee('Soft skills')
        ->assertSee('Certification')
        ->assertSee('Rejected cloud certification')
        ->assertSee('Coordinator note')
        ->assertSee('Please add the signed certificate.')
        ->assertSee('co-status-rejected', false);
});
