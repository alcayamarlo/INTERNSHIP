<?php

use App\Enums\CertificateType;
use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\SkillType;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipRequirement;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use App\Services\CompetencyMatchingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function certificateStudent(): Student
{
    $user = User::factory()->create(['role' => UserRole::Student]);

    return Student::create(['user_id' => $user->id]);
}

function certificatePayload(array $overrides = []): array
{
    return [
        'certificate_type' => 'certificate',
        'name' => 'Computer Systems Servicing NC II',
        'skill_type' => 'technical',
        'proficiency_level' => 'intermediate',
        'skills' => ['Troubleshooting', 'Network cabling'],
        'evidence' => UploadedFile::fake()->create('css-nc2.pdf', 300, 'application/pdf'),
        'issuing_organization' => 'TESDA',
        'obtained_at' => '2025-03-01',
        'description' => 'Passed the national assessment.',
        ...$overrides,
    ];
}

beforeEach(fn () => Storage::fake('private'));

// ---------- Wizard validation, step by step ----------

it('reports step 1 errors with plain messages', function () {
    $student = certificateStudent();

    $this->actingAs($student->user)
        ->postJson(route('student.competencies.store'), certificatePayload([
            'certificate_type' => '', 'name' => '', 'skill_type' => '', 'proficiency_level' => '',
        ]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.certificate_type.0', 'Please choose Seminar, Workshop, Training, or Certificate.')
        ->assertJsonPath('errors.name.0', 'Please type the title of your certificate.')
        ->assertJsonPath('errors.skill_type.0', 'Please choose Technical or Soft Skill.')
        ->assertJsonPath('errors.proficiency_level.0', 'Please choose Beginner, Intermediate, or Advanced.');

    // The old category options and the Expert level are no longer offered.
    $this->postJson(route('student.competencies.store'), certificatePayload(['certificate_type' => 'soft', 'proficiency_level' => 'expert']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['certificate_type', 'proficiency_level']);
});

it('requires 1 to 15 learned skills and blocks duplicates within a certificate', function () {
    $student = certificateStudent();
    $this->actingAs($student->user);

    $this->postJson(route('student.competencies.store'), certificatePayload(['skills' => []]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.skills.0', 'Add at least 1 skill you learned.');

    $this->postJson(route('student.competencies.store'), certificatePayload(['skills' => ['Git', '  git  ']]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.skills.0', 'You already added this skill.');

    $this->postJson(route('student.competencies.store'), certificatePayload(['skills' => array_map(fn ($n) => 'Skill '.$n, range(1, 16))]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('skills');

    expect(StudentCompetency::count())->toBe(0);
});

it('requires a PDF, JPG, or PNG certificate up to 5 MB', function () {
    $student = certificateStudent();
    $this->actingAs($student->user);

    $this->postJson(route('student.competencies.store'), certificatePayload(['evidence' => null]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.evidence.0', 'Please upload a photo or PDF of your certificate.');
    $this->postJson(route('student.competencies.store'), certificatePayload(['evidence' => UploadedFile::fake()->create('cert.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.evidence.0', 'Only PDF, JPG, or PNG files are allowed.');
    $this->postJson(route('student.competencies.store'), certificatePayload(['evidence' => UploadedFile::fake()->create('cert.pdf', 6000, 'application/pdf')]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.evidence.0', 'The file is too large. The limit is 5 MB.');
});

it('saves a certificate with its learned skills on a private disk and returns the refreshed overview', function () {
    $student = certificateStudent();

    $response = $this->actingAs($student->user)
        ->postJson(route('student.competencies.store'), certificatePayload(['skills' => [' Troubleshooting ', 'Network   cabling']]))
        ->assertCreated()
        ->assertJsonPath('message', 'Certificate added. It now shows on your profile and resume.')
        ->assertJsonPath('overview.skills.0.learned_skills', ['Troubleshooting', 'Network cabling'])
        ->assertJsonPath('overview.skills.0.type_label', 'Certificate')
        ->assertJsonPath('overview.skills.0.skill_type_label', 'Technical');

    $certificate = StudentCompetency::findOrFail($response->json('id'));
    expect($certificate->certificate_type)->toBe(CertificateType::Certificate)
        ->and($certificate->skill_type)->toBe(SkillType::Technical)
        ->and($certificate->category)->toBe(CompetencyCategory::Certification)
        ->and(Storage::disk('private')->exists($certificate->evidence_path))->toBeTrue()
        ->and($certificate->learnedSkills->pluck('name')->all())->toBe(['Troubleshooting', 'Network cabling']);

    $this->get(route('student.competencies.file', $certificate))->assertOk();
});

it('edits a certificate through the same wizard and keeps the file when none is uploaded', function () {
    $student = certificateStudent();
    $this->actingAs($student->user)->postJson(route('student.competencies.store'), certificatePayload())->assertCreated();
    $certificate = StudentCompetency::firstOrFail();
    $path = $certificate->evidence_path;

    $this->putJson(route('student.competencies.update', $certificate), certificatePayload([
        'evidence' => null,
        'certificate_type' => 'workshop',
        'name' => 'Git and GitHub Workshop',
        'skill_type' => 'technical',
        'skills' => ['Branching'],
    ]))->assertOk()->assertJsonPath('message', 'Certificate updated.');

    $certificate->refresh();
    expect($certificate->name)->toBe('Git and GitHub Workshop')
        ->and($certificate->category)->toBe(CompetencyCategory::Workshop)
        ->and($certificate->evidence_path)->toBe($path)
        ->and($certificate->learnedSkills->pluck('name')->all())->toBe(['Branching']);

    $this->get(route('student.competencies.edit', $certificate))
        ->assertRedirect(route('student.competencies.index', ['edit' => $certificate->id]));
});

// ---------- Ownership ----------

it('lets only the owning student change or view a certificate and its skills', function () {
    $owner = certificateStudent();
    $this->actingAs($owner->user)->postJson(route('student.competencies.store'), certificatePayload())->assertCreated();
    $certificate = StudentCompetency::firstOrFail();
    $other = certificateStudent();

    $this->actingAs($other->user)
        ->putJson(route('student.competencies.update', $certificate), certificatePayload(['skills' => ['Hacked']]))
        ->assertForbidden();
    $this->deleteJson(route('student.competencies.destroy', $certificate))->assertForbidden();
    $this->get(route('student.competencies.file', $certificate))->assertForbidden();

    $institution = Institution::create(['name' => 'Ownership College']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);
    $owner->update(['institution_id' => $institution->id]);
    $this->actingAs($coordinatorUser)
        ->putJson(route('student.competencies.update', $certificate), certificatePayload(['skills' => ['Hacked']]))
        ->assertForbidden();

    expect($certificate->fresh()->learnedSkills->pluck('name')->all())->toBe(['Troubleshooting', 'Network cabling']);
});

// ---------- Older Documents-tab certificates ----------

it('lists old certificates without details, keeps them out of the chart, and completes them in the wizard', function () {
    $student = certificateStudent();
    Storage::disk('private')->put('certificates/old.pdf', '%PDF-1.4 old');
    $old = Certificate::create([
        'student_id' => $student->id,
        'title' => 'Food Safety Seminar',
        'issuer' => 'City Health Office',
        'issue_date' => '2024-02-02',
        'file_path' => 'certificates/old.pdf',
    ]);

    $this->actingAs($student->user)
        ->get(route('student.competencies.index'))
        ->assertOk()
        ->assertSee('Older certificates')
        ->assertSee('Food Safety Seminar');
    $overview = app(\App\Services\StudentCompetencyOverviewService::class)->forStudent($student);
    expect($overview['skills'])->toBe([]);

    $this->postJson(route('student.competencies.complete-legacy', $old), certificatePayload([
        'evidence' => null,
        'certificate_type' => 'seminar',
        'name' => 'Food Safety Seminar',
        'skill_type' => 'soft',
        'skills' => ['Safe food handling'],
    ]))->assertCreated()->assertJsonPath('completed_legacy_id', $old->id);

    $certificate = StudentCompetency::firstOrFail();
    expect($certificate->evidence_path)->toBe('certificates/old.pdf')
        ->and($old->fresh()->student_competency_id)->toBe($certificate->id);
    $this->get(route('student.competencies.index'))->assertOk()->assertDontSee('Older certificates');

    // Deleting the completed certificate removes it as one item, and the shared file with it.
    $this->deleteJson(route('student.competencies.destroy', $certificate))->assertOk();
    expect(Certificate::count())->toBe(0)
        ->and(Storage::disk('private')->exists('certificates/old.pdf'))->toBeFalse();
});

// ---------- Chart and match % ----------

it('builds the Technical and Soft chart from the skill type field', function () {
    $student = certificateStudent();
    $this->actingAs($student->user);
    $this->postJson(route('student.competencies.store'), certificatePayload(['skill_type' => 'soft', 'name' => 'Leadership Training', 'certificate_type' => 'training']))->assertCreated();
    $this->postJson(route('student.competencies.store'), certificatePayload(['proficiency_level' => 'advanced']))->assertCreated();

    $groups = app(\App\Services\StudentCompetencyOverviewService::class)->forStudent($student->fresh())['groups'];

    expect($groups['soft']['count'])->toBe(1)
        ->and($groups['technical']['count'])->toBe(1)
        ->and($groups['technical']['points'])->toBe(75)
        ->and($groups['certification']['count'])->toBe(1);
});

it('keeps the match score based only on title and earned level', function () {
    $student = certificateStudent();
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Match Co', 'contact_person' => 'HR']);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'IT Intern', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);
    InternshipRequirement::create(['internship_id' => $internship->id, 'requirement_name' => 'Networking', 'required_level' => ProficiencyLevel::Intermediate]);
    $legacy = StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Networking',
        'category' => CompetencyCategory::Technical,
        'proficiency_level' => ProficiencyLevel::Intermediate,
    ]);
    $matcher = app(CompetencyMatchingService::class);
    $before = $matcher->calculateMatch($student, $internship);

    // Changing type, skill type, and learned skills does not move the score...
    $legacy->update(['certificate_type' => CertificateType::Seminar, 'skill_type' => SkillType::Soft]);
    $legacy->learnedSkills()->create(['name' => 'Subnetting']);
    expect($matcher->calculateMatch($student->fresh(), $internship))->toBe($before);

    // ...only the earned level does, exactly as before.
    $legacy->update(['proficiency_level' => ProficiencyLevel::Beginner]);
    expect($matcher->calculateMatch($student->fresh(), $internship))->toBeLessThan($before);
});

// ---------- Migration mapping ----------

it('maps old competency categories and credentials without losing data', function () {
    $student = certificateStudent();
    $migration = require database_path('migrations/2026_10_31_000002_turn_competencies_into_certificates.php');
    $migration->down();

    $rows = collect(['certification', 'training', 'seminar', 'workshop', 'skill', 'technical', 'soft'])
        ->mapWithKeys(fn (string $category) => [$category => DB::table('student_competencies')->insertGetId([
            'student_id' => $student->id,
            'name' => 'Old '.$category,
            'category' => $category,
            'proficiency_level' => 'advanced',
            'assessment_name' => $category === 'skill' ? '  Python   Skills Assessment ' : null,
            'description' => 'Kept description',
            'issuing_organization' => 'Kept issuer',
            'obtained_at' => '2024-01-01',
            'evidence_path' => 'competencies/'.$category.'.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ])]);

    $migration->up();

    $expected = [
        'certification' => ['certificate', 'technical'],
        'training' => ['training', 'technical'],
        'seminar' => ['seminar', 'technical'],
        'workshop' => ['workshop', 'technical'],
        'skill' => ['certificate', 'technical'],
        'technical' => ['certificate', 'technical'],
        'soft' => ['certificate', 'soft'],
    ];
    foreach ($rows as $category => $id) {
        $row = DB::table('student_competencies')->find($id);
        expect([$row->certificate_type, $row->skill_type])->toBe($expected[$category])
            ->and($row->category)->toBe($category)
            ->and($row->name)->toBe('Old '.$category)
            ->and($row->proficiency_level)->toBe('advanced')
            ->and($row->description)->toBe('Kept description')
            ->and($row->issuing_organization)->toBe('Kept issuer')
            ->and($row->evidence_path)->toBe('competencies/'.$category.'.pdf');
    }
    expect(DB::table('student_competency_skills')->where('student_competency_id', $rows['skill'])->pluck('name')->all())
        ->toBe(['Python Skills Assessment'])
        ->and(DB::table('student_competency_skills')->count())->toBe(1);

    // Reversible: rolling back keeps every original column intact.
    $migration->down();
    expect(DB::table('student_competencies')->find($rows['skill'])->assessment_name)->toBe('  Python   Skills Assessment ');
    $migration->up();
});

// ---------- Display ----------

it('shows certificates with their skills on the employer applicant page', function () {
    $student = certificateStudent();
    $this->actingAs($student->user)->postJson(route('student.competencies.store'), certificatePayload())->assertCreated();
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'View Co', 'contact_person' => 'HR']);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Intern', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => 'applied', 'match_percentage' => 10, 'applied_at' => now()]);

    $this->actingAs($employerUser)
        ->get(route('employer.applicants.show', $application))
        ->assertOk()
        ->assertSeeInOrder(['<h2>Certificates</h2>', 'Computer Systems Servicing NC II', 'TESDA', 'Troubleshooting'], false);
});
