<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\CharacterReference;
use App\Models\DeploymentRequirement;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\SkillTag;
use App\Models\Student;
use App\Models\StudentSkill;
use App\Models\User;
use App\Services\CompanyVisibleProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function bioDataStudent(array $attributes = []): Student
{
    $user = User::factory()->create(['role' => UserRole::Student, 'phone' => '09170000000']);

    return Student::create([
        'user_id' => $user->id,
        'privacy_consent' => true,
        'privacy_consent_at' => now(),
        ...$attributes,
    ]);
}

function bioDataEmployer(string $name = 'Acme Corp'): array
{
    $user = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $user->id, 'company_name' => $name, 'contact_person' => 'HR']);

    return [$user, $employer];
}

function bioDataInternship(Employer $employer, array $attributes = []): Internship
{
    return Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Web Intern',
        'description' => 'Build things.',
        'work_setup' => 'remote',
        'status' => 'open',
        ...$attributes,
    ]);
}

function bioDataApply(Student $student, Internship $internship, ApplicationStatus $status = ApplicationStatus::Applied): InternshipApplication
{
    return InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => $status,
        'match_percentage' => 50,
        'applied_at' => now(),
    ]);
}

// ---------- Skills ----------

it('adds a skill as a chip, normalizes it, and reuses the catalog spelling', function () {
    // "Python" ships in the starter skill catalog.
    $student = bioDataStudent();

    $this->actingAs($student->user)
        ->postJson(route('student.skills.store'), ['name' => '   python  '])
        ->assertCreated()
        ->assertJsonPath('skill.name', 'Python')
        ->assertJsonPath('count', 1);

    $this->postJson(route('student.skills.store'), ['name' => 'Public   Speaking', 'level' => 'advanced'])
        ->assertCreated()
        ->assertJsonPath('skill.name', 'Public Speaking')
        ->assertJsonPath('skill.level', 'advanced');

    expect(SkillTag::where('name_normalized', 'python')->count())->toBe(1)
        ->and($student->studentSkills()->count())->toBe(2)
        // The employer-managed skill catalog is a different feature and stays untouched.
        ->and(Skill::count())->toBe(0);
});

it('blocks duplicate skills case-insensitively', function () {
    $student = bioDataStudent();

    $this->actingAs($student->user)->postJson(route('student.skills.store'), ['name' => 'Laravel'])->assertCreated();
    $this->postJson(route('student.skills.store'), ['name' => 'LARAVEL'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.name.0', '"Laravel" is already in your skills.');

    expect($student->studentSkills()->count())->toBe(1);
});

it('limits each student to 20 skills with a clear message', function () {
    $student = bioDataStudent();
    $this->actingAs($student->user);

    foreach (range(1, StudentSkill::MAX_PER_STUDENT) as $number) {
        $this->postJson(route('student.skills.store'), ['name' => 'Skill '.$number])->assertCreated();
    }

    $this->postJson(route('student.skills.store'), ['name' => 'One Too Many'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.name.0', 'You can add up to 20 skills. Remove one to add another.');

    expect($student->studentSkills()->count())->toBe(20);
});

it('rejects blank skills and falls back to a redirect without JavaScript', function () {
    $student = bioDataStudent();

    $this->actingAs($student->user)
        ->postJson(route('student.skills.store'), ['name' => '    '])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    $this->post(route('student.skills.store'), ['name' => 'Figma'])
        ->assertRedirect(route('student.profile.edit').'#skills');
});

it('suggests catalog skills by prefix and leaves out skills the student already has', function () {
    $student = bioDataStudent();
    SkillTag::create(['name' => 'PyTorch']);
    $this->actingAs($student->user)->postJson(route('student.skills.store'), ['name' => 'PyTorch'])->assertCreated();

    $suggestions = $this->getJson(route('student.skills.suggest', ['q' => 'py']))->assertOk()->json('suggestions');
    expect($suggestions)->toContain('Python')->not->toContain('PyTorch')->not->toContain('PHP');
});

it('updates a skill level and removes a skill, but only for its owner', function () {
    $student = bioDataStudent();
    $this->actingAs($student->user)->postJson(route('student.skills.store'), ['name' => 'SQL'])->assertCreated();
    $studentSkill = $student->studentSkills()->firstOrFail();

    $this->patchJson(route('student.skills.update', $studentSkill), ['level' => 'intermediate'])
        ->assertOk()
        ->assertJsonPath('skill.level_label', 'Intermediate');

    $other = bioDataStudent();
    $this->actingAs($other->user)->patchJson(route('student.skills.update', $studentSkill), ['level' => 'advanced'])->assertForbidden();
    $this->deleteJson(route('student.skills.destroy', $studentSkill))->assertForbidden();

    $this->actingAs($student->user)->deleteJson(route('student.skills.destroy', $studentSkill))->assertOk()->assertJsonPath('count', 0);
    expect(StudentSkill::count())->toBe(0);
});

// ---------- Privacy consent ----------

it('blocks profile saves until the student gives privacy consent, then records it with a timestamp', function () {
    $student = bioDataStudent(['privacy_consent' => false, 'privacy_consent_at' => null]);

    $this->actingAs($student->user)
        ->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('Data Privacy Act of 2012, RA 10173');

    $this->putJson(route('student.profile.update'), [
        'profile_section' => 'personal',
        'first_name' => 'Ana',
        'last_name' => 'Cruz',
        'gender' => 'Female',
        'date_of_birth' => '2003-01-01',
    ])->assertUnprocessable()->assertJsonValidationErrors('privacy_consent');
    $this->postJson(route('student.skills.store'), ['name' => 'Excel'])->assertUnprocessable()->assertJsonValidationErrors('privacy_consent');

    $this->postJson(route('student.profile.consent'), ['privacy_consent' => '1'])->assertOk();

    $student->refresh();
    expect($student->privacy_consent)->toBeTrue()
        ->and($student->privacy_consent_at)->not->toBeNull();
    $this->postJson(route('student.skills.store'), ['name' => 'Excel'])->assertCreated();
});

// ---------- Per-tab saves ----------

it('saves one tab at a time and validates only that tab', function () {
    $student = bioDataStudent(['first_name' => 'Ana', 'last_name' => 'Cruz']);

    $this->actingAs($student->user)
        ->putJson(route('student.profile.update'), [
            'profile_section' => 'contact',
            'email' => $student->user->email,
            'phone' => '0917 123 4567',
            'address' => '45 Osmeña Blvd',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'zip_code' => '6000',
            'emergency_contact_name' => 'Maria Cruz',
            'emergency_contact_relationship' => 'Mother',
            'emergency_contact_address' => '45 Osmeña Blvd',
            'emergency_contact_phone' => '09181112222',
            'github_url' => 'https://github.com/anacruz',
        ])
        ->assertOk();

    expect($student->fresh()->emergency_contact_name)->toBe('Maria Cruz')
        ->and($student->fresh()->github_url)->toBe('https://github.com/anacruz')
        ->and($student->fresh()->first_name)->toBe('Ana');

    $this->putJson(route('student.profile.update'), ['profile_section' => 'personal', 'first_name' => 'Ana', 'last_name' => 'Cruz'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['gender', 'date_of_birth'])
        ->assertJsonMissingValidationErrors(['emergency_contact_name', 'elementary_school']);

    $this->put(route('student.profile.update'), [
        'profile_section' => 'personal',
        'first_name' => 'Ana',
        'last_name' => 'Cruz',
        'gender' => 'Female',
        'date_of_birth' => '2003-01-01',
        'civil_status' => 'Single',
        'show_civil_status' => '1',
    ])->assertRedirect(route('student.profile.edit').'#personal');
    expect($student->fresh()->show_civil_status)->toBeTrue();
});

it('adds, edits, and removes optional experience entries', function () {
    $student = bioDataStudent();
    $this->actingAs($student->user);

    $this->postJson(route('student.experiences.store'), [
        'organization' => 'Jollibee',
        'position' => 'Service Crew',
        'start_date' => '2023-06-01',
        'is_current' => '1',
    ])->assertCreated()->assertJsonPath('message', 'Experience added.');
    $experience = $student->experiences()->firstOrFail();
    expect($experience->is_current)->toBeTrue()->and($experience->end_date)->toBeNull();

    $this->putJson(route('student.experiences.update', $experience), [
        'organization' => 'Jollibee',
        'position' => 'Shift Lead',
        'start_date' => '2023-06-01',
        'is_current' => '0',
    ])->assertUnprocessable()->assertJsonValidationErrors('end_date');

    $this->putJson(route('student.experiences.update', $experience), [
        'organization' => 'Jollibee',
        'position' => 'Shift Lead',
        'start_date' => '2023-06-01',
        'end_date' => '2024-03-31',
    ])->assertOk();
    expect($experience->fresh()->position)->toBe('Shift Lead');

    $this->deleteJson(route('student.experiences.destroy', $experience))->assertOk();
    expect($student->experiences()->count())->toBe(0);
});

it('caps character references at three', function () {
    $student = bioDataStudent();
    $this->actingAs($student->user);
    $reference = ['position' => 'Instructor', 'organization' => "St. Cecilia's College", 'contact_number' => '0917 555 0101'];

    foreach (['Prof. A', 'Prof. B', 'Prof. C'] as $name) {
        $this->postJson(route('student.references.store'), ['name' => $name, ...$reference])->assertCreated();
    }
    $this->postJson(route('student.references.store'), ['name' => 'Prof. D', ...$reference])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    expect($student->characterReferences()->count())->toBe(3)
        ->and($student->characterReferences()->value('contact_number'))->toBe('09175550101');
});

// ---------- Completion bar ----------

it('weights profile completion by tab and ignores experience', function () {
    Storage::fake('private');
    $student = bioDataStudent([
        'first_name' => 'Ana', 'last_name' => 'Cruz', 'gender' => 'Female', 'date_of_birth' => '2003-01-01',
        'address' => '45 Osmeña Blvd', 'city' => 'Cebu City', 'province' => 'Cebu',
        'emergency_contact_name' => 'Maria Cruz', 'emergency_contact_relationship' => 'Mother',
        'emergency_contact_address' => 'Cebu City', 'emergency_contact_phone' => '09181112222',
        'department' => 'CCS', 'program' => 'BSIT', 'year_level' => '3rd Year', 'expected_graduation' => '2027-05-01',
        'elementary_school' => 'Cebu Central', 'elementary_year_graduated' => 2015,
        'high_school' => 'Cebu Science', 'high_school_year_graduated' => 2021,
    ]);

    $summary = fn () => $student->fresh()->profileCompletionSummary();
    expect($summary()['percentage'])->toBe(45)
        ->and($summary()['remaining_steps'])->toBe(4)
        ->and($summary()['missing_steps'])->toContain('Skills: add at least 3 skills (0 of 3)');

    $student->experiences()->create(['organization' => 'Org', 'position' => 'Volunteer', 'start_date' => '2024-01-01', 'is_current' => true]);
    expect($summary()['percentage'])->toBe(45);

    foreach (['Kotlin', 'Rust', 'Juggling'] as $name) {
        $student->studentSkills()->create(['skill_tag_id' => SkillTag::create(['name' => $name])->id]);
    }
    expect($summary()['percentage'])->toBe(60);

    Portfolio::create(['student_id' => $student->id, 'title' => 'Capstone', 'type' => 'project', 'category' => 'project', 'project_url' => 'https://example.com']);
    expect($summary()['percentage'])->toBe(75);

    $student->characterReferences()->create(['name' => 'Prof. A', 'position' => 'Adviser', 'organization' => 'SCC', 'contact_number' => '09170000001']);
    expect($summary()['percentage'])->toBe(85);

    Resume::create(['student_id' => $student->id, 'content' => [], 'file_path' => 'resumes/ana.pdf', 'generated_at' => now()]);
    expect($summary()['percentage'])->toBe(85)
        ->and($summary()['missing_steps'])->toBe(['Documents: add at least one required document']);

    Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Certificate of registration (COR)',
        'type' => 'transcript',
        'category' => 'deployment_document',
        'deployment_requirement_id' => DeploymentRequirement::where('code', 'certificate-of-registration')->value('id'),
        'file_path' => 'portfolios/cor.pdf',
        'verification_status' => 'submitted',
    ]);
    expect($summary()['percentage'])->toBe(100)
        ->and($summary()['remaining_steps'])->toBe(0);
});

// ---------- Documents ----------

it('accepts only PDF, JPG, or PNG documents up to 5 MB', function () {
    Storage::fake('private');
    $student = bioDataStudent();
    $requirement = DeploymentRequirement::where('code', 'transcript-of-records')->firstOrFail();
    $this->actingAs($student->user);

    $this->postJson(route('student.portfolio.deployment-requirements.store', $requirement), [
        'file' => UploadedFile::fake()->create('tor.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    ])->assertUnprocessable()->assertJsonPath('errors.file.0', 'Upload a PDF, JPG, or PNG file.');

    $this->postJson(route('student.portfolio.deployment-requirements.store', $requirement), [
        'file' => UploadedFile::fake()->create('tor.pdf', 6000, 'application/pdf'),
    ])->assertUnprocessable()->assertJsonPath('errors.file.0', 'The file must be 5 MB or smaller.');

    $this->postJson(route('student.portfolio.deployment-requirements.store', $requirement), [
        'file' => UploadedFile::fake()->create('tor.pdf', 400, 'application/pdf'),
    ])->assertOk();
});

it('only accepts an ID upload when an applied-to internship requested it', function () {
    Storage::fake('private');
    $student = bioDataStudent();
    [, $employer] = bioDataEmployer();
    $nbi = DeploymentRequirement::where('code', 'nbi-clearance')->firstOrFail();
    $upload = fn () => $this->postJson(route('student.portfolio.deployment-requirements.store', $nbi), [
        'file' => UploadedFile::fake()->create('nbi.pdf', 100, 'application/pdf'),
    ]);

    $this->actingAs($student->user);
    $upload()->assertForbidden();
    $this->get(route('student.profile.edit'))->assertOk()->assertDontSee('NBI clearance');

    bioDataApply($student, bioDataInternship($employer, ['requested_documents' => ['nbi-clearance']]));
    $this->get(route('student.profile.edit'))->assertOk()->assertSee('NBI clearance')->assertSee('Requested by: Web Intern');
    $upload()->assertOk();
});

// ---------- Character reference requirement ----------

it('blocks applying to an internship that requires a character reference until one is added', function () {
    $student = bioDataStudent(['year_level' => '4th Year', 'major_subjects_passed' => true]);
    $resume = Resume::create(['student_id' => $student->id, 'content' => []]);
    [, $employer] = bioDataEmployer();
    $internship = bioDataInternship($employer, ['requires_character_reference' => true]);

    $this->actingAs($student->user)
        ->postJson(route('student.internships.apply', $internship), ['resume_id' => $resume->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('character_reference');

    CharacterReference::create(['student_id' => $student->id, 'name' => 'Prof. A', 'position' => 'Adviser', 'organization' => 'SCC', 'contact_number' => '09170000001']);
    $this->postJson(route('student.internships.apply', $internship), ['resume_id' => $resume->id])->assertOk();
});

it('lets an employer mark an internship as requiring a reference and request IDs', function () {
    [$employerUser] = bioDataEmployer();

    $this->actingAs($employerUser)->post(route('employer.internships.store'), [
        'title' => 'Data Intern',
        'description' => 'Analyze data.',
        'work_setup' => 'remote',
        'status' => 'open',
        'requires_character_reference' => '1',
        'requested_documents' => ['nbi-clearance', 'passport'],
    ])->assertRedirect(route('employer.internships.index'));

    $internship = Internship::where('title', 'Data Intern')->firstOrFail();
    expect($internship->requires_character_reference)->toBeTrue()
        ->and($internship->requested_documents)->toBe(['nbi-clearance', 'passport']);

    $this->post(route('employer.internships.store'), [
        'title' => 'Bad Intern', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open',
        'requested_documents' => ['certificate-of-registration'],
    ])->assertSessionHasErrors('requested_documents.0');
});

// ---------- Company-visible profile ----------

it('hides sensitive fields from companies unless the student shows them', function () {
    $student = bioDataStudent(['civil_status' => 'Single', 'religion' => 'Catholic', 'height_cm' => 160, 'weight_kg' => 52.5, 'show_religion' => true]);
    [$employerUser, $employer] = bioDataEmployer();
    bioDataApply($student, bioDataInternship($employer));

    $profile = app(CompanyVisibleProfile::class)->for($student, $employerUser);

    expect($profile['personal'])->toHaveKey('Religion', 'Catholic')
        ->and($profile['personal'])->not->toHaveKeys(['Civil status', 'Height', 'Weight']);

    $student->update(['show_height' => true, 'show_weight' => true]);
    $profile = app(CompanyVisibleProfile::class)->for($student->fresh(), $employerUser);
    expect($profile['personal']['Height'])->toBe('160 cm')
        ->and($profile['personal']['Weight'])->toBe('52.5 kg');
});

it('hides contact details, references, and documents unless the student has an active application to that company', function () {
    $student = bioDataStudent(['address' => '45 Osmeña Blvd', 'city' => 'Cebu City', 'emergency_contact_name' => 'Maria Cruz']);
    $student->characterReferences()->create(['name' => 'Prof. A', 'position' => 'Adviser', 'organization' => 'SCC', 'contact_number' => '09170000001']);
    [$employerUser, $employer] = bioDataEmployer();
    [$otherEmployerUser] = bioDataEmployer('Other Co');
    $helper = app(CompanyVisibleProfile::class);

    expect($helper->for($student, $otherEmployerUser))
        ->contact->toBeNull()
        ->references->toBe([])
        ->documents->toBe([])
        ->emergency_contact->toBeNull();

    $application = bioDataApply($student, bioDataInternship($employer));
    $profile = $helper->for($student, $employerUser);
    expect($profile['contact']['Email'])->toBe($student->user->email)
        ->and($profile['contact']['City address'])->toBe('45 Osmeña Blvd, Cebu City')
        ->and($profile['references'])->toHaveCount(1)
        ->and($profile['emergency_contact'])->toBeNull();

    Placement::create([
        'application_id' => $application->id, 'student_id' => $student->id, 'employer_id' => $employer->id,
        'internship_id' => $application->internship_id, 'start_date' => now(), 'required_hours' => 486,
    ]);
    expect($helper->for($student, $employerUser)['emergency_contact'])->toBe(['Name' => 'Maria Cruz']);

    $application->update(['status' => ApplicationStatus::Withdrawn]);
    expect($helper->for($student, $employerUser)['contact'])->toBeNull();
});

it('shows a requested ID only to the company whose internship asked for it', function () {
    Storage::fake('private');
    Storage::disk('private')->put('portfolios/nbi.pdf', '%PDF-1.4 id');
    $student = bioDataStudent();
    [$requestingUser, $requestingEmployer] = bioDataEmployer('Requests IDs');
    [$otherUser, $otherEmployer] = bioDataEmployer('No IDs');
    $requestingApplication = bioDataApply($student, bioDataInternship($requestingEmployer, ['requested_documents' => ['nbi-clearance']]));
    $otherApplication = bioDataApply($student, bioDataInternship($otherEmployer));
    $nbi = Portfolio::create([
        'student_id' => $student->id,
        'title' => 'NBI clearance',
        'type' => 'transcript',
        'category' => 'deployment_document',
        'deployment_requirement_id' => DeploymentRequirement::where('code', 'nbi-clearance')->value('id'),
        'file_path' => 'portfolios/nbi.pdf',
        'verification_status' => 'submitted',
    ]);
    $consent = Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Parent consent',
        'type' => 'transcript',
        'category' => 'deployment_document',
        'deployment_requirement_id' => DeploymentRequirement::where('code', 'parent-guardian-consent')->value('id'),
        'file_path' => 'portfolios/nbi.pdf',
        'verification_status' => 'submitted',
    ]);
    $helper = app(CompanyVisibleProfile::class);

    expect(collect($helper->for($student, $requestingUser)['documents'])->pluck('title')->all())->toBe(['NBI clearance'])
        ->and($helper->for($student, $otherUser)['documents'])->toBe([]);

    $this->actingAs($requestingUser)
        ->get(route('employer.applicants.portfolios.preview', [$requestingApplication, $nbi]))
        ->assertOk();
    $this->actingAs($otherUser)
        ->get(route('employer.applicants.portfolios.preview', [$otherApplication, $nbi]))
        ->assertForbidden();
    $this->actingAs($requestingUser)
        ->get(route('employer.applicants.portfolios.preview', [$requestingApplication, $consent]))
        ->assertForbidden();
});

it('renders the applicant page from the company-visible profile only', function () {
    $student = bioDataStudent(['religion' => 'Catholic', 'show_religion' => false, 'high_school' => 'Cebu Science', 'high_school_year_graduated' => 2021]);
    $student->studentSkills()->create(['skill_tag_id' => SkillTag::create(['name' => 'Tailwind'])->id, 'level' => 'advanced']);
    [$employerUser, $employer] = bioDataEmployer();
    $application = bioDataApply($student, bioDataInternship($employer));

    $this->actingAs($employerUser)
        ->get(route('employer.applicants.show', $application))
        ->assertOk()
        ->assertSee('Tailwind · Advanced')
        ->assertSee('Cebu Science (2021)')
        ->assertSee($student->user->email)
        ->assertDontSee('Catholic');
});

// ---------- Skills stay separate from competencies ----------

it('never counts self-declared skills toward the competency match score', function () {
    $student = bioDataStudent();
    [, $employer] = bioDataEmployer();
    $internship = bioDataInternship($employer);
    \App\Models\InternshipRequirement::create([
        'internship_id' => $internship->id,
        'requirement_name' => 'Python',
        'required_level' => \App\Enums\ProficiencyLevel::Intermediate,
    ]);
    $matcher = app(\App\Services\CompetencyMatchingService::class);
    $before = $matcher->calculateMatch($student, $internship);

    $this->actingAs($student->user)->postJson(route('student.skills.store'), ['name' => 'Python', 'level' => 'advanced'])->assertCreated();
    expect($matcher->calculateMatch($student->fresh(), $internship))->toBe($before);

    \App\Models\StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Python',
        'category' => \App\Enums\CompetencyCategory::Technical,
        'proficiency_level' => \App\Enums\ProficiencyLevel::Advanced,
    ]);
    expect($matcher->calculateMatch($student->fresh(), $internship))->toBeGreaterThan($before);
});

it('labels a skill "Has certificate" when a same-named competency has a certificate, without touching the competency', function () {
    $student = bioDataStudent();
    $competency = \App\Models\StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Public Speaking',
        'category' => \App\Enums\CompetencyCategory::Soft,
        'proficiency_level' => \App\Enums\ProficiencyLevel::Advanced,
        'evidence_path' => 'competencies/'.$student->id.'/speaking.pdf',
        'evidence_name' => 'speaking.pdf',
        'verification_status' => 'evidence_submitted',
    ]);
    $original = $competency->fresh()->getAttributes();

    $this->actingAs($student->user)
        ->postJson(route('student.skills.store'), ['name' => '  public   speaking '])
        ->assertCreated()
        ->assertJsonPath('skill.has_certificate', true);
    $this->postJson(route('student.skills.store'), ['name' => 'Singing'])
        ->assertCreated()
        ->assertJsonPath('skill.has_certificate', false);

    expect($competency->fresh()->getAttributes())->toBe($original)
        ->and(\App\Models\StudentCompetency::count())->toBe(1);
});

it('puts skills on the resume as their own section, separate from certificates', function () {
    Storage::fake('private');
    $student = bioDataStudent(['program' => 'BSIT']);
    \App\Models\StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Database Design',
        'category' => \App\Enums\CompetencyCategory::Technical,
        'proficiency_level' => \App\Enums\ProficiencyLevel::Intermediate,
    ]);
    $student->studentSkills()->create(['skill_tag_id' => SkillTag::where('name', 'Singing')->value('id'), 'level' => 'advanced']);

    $content = app(\App\Services\ResumeBuilderService::class)->build($student)->content;

    expect($content['skills'])->toBe(['Singing'])
        ->and(collect($content['certificates'])->pluck('title')->all())->toBe(['Database Design']);

    $html = view('student.resume.pdf', ['content' => $content, 'photo' => null])->render();
    // Skills sit in the sidebar; certificates have their own section in the main column.
    $skillsAt = strpos($html, '<div class="side-heading">Skills</div>');
    $mainAt = strpos($html, '<div class="main">');
    $certificatesAt = strpos($html, '<div class="bar">Certificates</div>');
    expect($skillsAt)->not->toBeFalse()
        ->and($mainAt)->toBeGreaterThan($skillsAt)
        ->and(strpos($html, '<li>Singing</li>'))->toBeGreaterThan($skillsAt)->toBeLessThan($mainAt)
        ->and($certificatesAt)->toBeGreaterThan($mainAt)
        ->and(strpos($html, 'Database Design'))->toBeGreaterThan($certificatesAt);
});

it('shows skills and competencies as separate sections to coordinators and employers', function () {
    $institution = \App\Models\Institution::create(['name' => 'Separate Sections College']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    \App\Models\Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);
    $student = bioDataStudent(['institution_id' => $institution->id]);
    \App\Models\StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Network Setup',
        'category' => \App\Enums\CompetencyCategory::Technical,
        'proficiency_level' => \App\Enums\ProficiencyLevel::Beginner,
    ]);
    $student->studentSkills()->create(['skill_tag_id' => SkillTag::where('name', 'Dancing')->value('id')]);
    [$employerUser, $employer] = bioDataEmployer();
    $application = bioDataApply($student, bioDataInternship($employer));

    $this->actingAs($coordinatorUser)
        ->get(route('coordinator.students.show', $student))
        ->assertOk()
        ->assertSeeInOrder(['Certificates', 'Network Setup', 'Skills', 'Dancing']);

    $this->actingAs($employerUser)
        ->get(route('employer.applicants.show', $application))
        ->assertOk()
        ->assertSeeInOrder(['<h2>Certificates</h2>', 'Network Setup', '<h2>Skills</h2>', 'Dancing'], false)
        ->assertSee('Not part of the match score.');
});
