<?php

use App\Enums\ApplicationStatus;
use App\Enums\CertificateType;
use App\Enums\ProficiencyLevel;
use App\Enums\SkillType;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\CharacterReference;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Resume;
use App\Models\SkillTag;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\StudentSkill;
use App\Models\User;
use App\Services\CompanyVisibleProfile;
use App\Services\ResumeBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
});

function resumeStudent(array $attributes = []): Student
{
    $user = User::factory()->create(['role' => UserRole::Student, 'name' => 'Ana Cruz', 'phone' => '09171234567']);

    return Student::create([
        'user_id' => $user->id,
        'privacy_consent' => true,
        'privacy_consent_at' => now(),
        ...$attributes,
    ]);
}

/** An existing resume row with a file, generated $minutesAgo minutes ago. */
function resumeOnDisk(Student $student, int $minutesAgo): Resume
{
    $path = 'resumes/student_'.$student->id.'_old_'.$minutesAgo.'.pdf';
    Storage::disk('private')->put($path, '%PDF-1.4 old');

    return Resume::create([
        'student_id' => $student->id,
        'file_path' => $path,
        'content' => ['personal' => ['name' => 'Old']],
        'generated_at' => now()->subMinutes($minutesAgo),
    ]);
}

function resumeApplication(Student $student, Resume $resume): InternshipApplication
{
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Acme', 'contact_person' => 'HR']);
    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Web Intern',
        'description' => 'Build things.',
        'work_setup' => 'remote',
        'status' => 'open',
    ]);

    return InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'resume_id' => $resume->id,
        'status' => ApplicationStatus::Applied,
        'match_percentage' => 50,
        'applied_at' => now(),
    ]);
}

function renderResumeHtml(Student $student, ?string $photo = null): string
{
    return view('student.resume.pdf', [
        'content' => app(CompanyVisibleProfile::class)->resume($student->fresh()),
        'photo' => $photo,
    ])->render();
}

it('keeps only the 3 latest resumes, deleting the oldest row and file', function () {
    $student = resumeStudent();
    $oldest = resumeOnDisk($student, 30);
    $middle = resumeOnDisk($student, 20);
    $newer = resumeOnDisk($student, 10);

    $this->actingAs($student->user)
        ->post(route('student.resume.generate'))
        ->assertRedirect(route('student.resume.index'));

    $remaining = Resume::where('student_id', $student->id)->orderByDesc('generated_at')->get();
    $newest = $remaining->first();

    expect($remaining)->toHaveCount(3)
        ->and($remaining->modelKeys())->not->toContain($oldest->id)
        ->and($remaining->modelKeys())->toContain($middle->id, $newer->id)
        ->and(Storage::disk('private')->exists($oldest->file_path))->toBeFalse()
        ->and(Storage::disk('private')->exists($middle->file_path))->toBeTrue()
        ->and(Storage::disk('private')->exists($newest->file_path))->toBeTrue();
});

it('lists resumes newest first with the limit note and View, Download, and Delete', function () {
    $student = resumeStudent();
    $older = resumeOnDisk($student, 120);
    $newer = resumeOnDisk($student, 5);

    $response = $this->actingAs($student->user)->get(route('student.resume.index'))->assertOk();

    $response->assertSeeInOrder([
        $newer->generated_at->format('M d, Y h:i A'),
        $older->generated_at->format('M d, Y h:i A'),
    ])
        ->assertSee('Only your 3 latest resumes are kept. Generating a new one removes the oldest.')
        ->assertSee(route('student.resume.view', $newer, false), false)
        ->assertSee(route('student.resume.download', $newer, false), false)
        ->assertSee(route('student.resume.destroy', $newer, false), false)
        ->assertDontSee('Archive');
});

it('never touches another student\'s resumes when pruning', function () {
    $student = resumeStudent();
    $other = resumeStudent();
    $otherResumes = collect([40, 30, 20, 10])->map(fn (int $minutes) => resumeOnDisk($other, $minutes));
    collect([30, 20, 10])->each(fn (int $minutes) => resumeOnDisk($student, $minutes));

    $this->actingAs($student->user)->post(route('student.resume.generate'));

    expect(Resume::where('student_id', $other->id)->count())->toBe(4);
    $otherResumes->each(fn (Resume $resume) => expect(Storage::disk('private')->exists($resume->file_path))->toBeTrue());
});

it('moves applications on a pruned resume to the new resume', function () {
    $student = resumeStudent();
    $oldest = resumeOnDisk($student, 30);
    resumeOnDisk($student, 20);
    resumeOnDisk($student, 10);
    $application = resumeApplication($student, $oldest);

    $this->actingAs($student->user)->post(route('student.resume.generate'));

    $newest = Resume::where('student_id', $student->id)->orderByDesc('generated_at')->orderByDesc('id')->first();
    expect(Resume::find($oldest->id))->toBeNull()
        ->and($application->fresh()->resume_id)->toBe($newest->id);
});

it('leaves students with more than 3 resumes alone until they generate again', function () {
    $student = resumeStudent();
    collect([50, 40, 30, 20, 10])->each(fn (int $minutes) => resumeOnDisk($student, $minutes));

    $this->actingAs($student->user)->get(route('student.resume.index'))->assertOk();
    expect(Resume::where('student_id', $student->id)->count())->toBe(5);

    $this->post(route('student.resume.generate'));
    expect(Resume::where('student_id', $student->id)->count())->toBe(3);
});

it('rolls back the new resume and keeps old ones when pruning fails', function () {
    $student = resumeStudent();
    $resumes = collect([30, 20, 10])->map(fn (int $minutes) => resumeOnDisk($student, $minutes));

    // Fail right after the old row is deleted, before any file is touched.
    \Illuminate\Support\Facades\DB::listen(function ($query) {
        if (str_starts_with(strtolower($query->sql), 'delete from "resumes"')) {
            throw new RuntimeException('Simulated failure');
        }
    });

    expect(fn () => app(ResumeBuilderService::class)->build($student))->toThrow(RuntimeException::class, 'Simulated failure');

    expect(Resume::where('student_id', $student->id)->count())->toBe(3)
        ->and(Storage::disk('private')->files('resumes'))->toHaveCount(3);
    $resumes->each(fn (Resume $resume) => expect(Storage::disk('private')->exists($resume->file_path))->toBeTrue());
});

it('lets a student delete only their own resume', function () {
    $student = resumeStudent();
    $intruder = resumeStudent();
    $resume = resumeOnDisk($student, 10);

    $this->actingAs($intruder->user)
        ->delete(route('student.resume.destroy', $resume))
        ->assertForbidden();
    expect(Resume::find($resume->id))->not->toBeNull();

    $this->actingAs($student->user)
        ->delete(route('student.resume.destroy', $resume))
        ->assertRedirect(route('student.resume.index'));
    expect(Resume::find($resume->id))->toBeNull()
        ->and(Storage::disk('private')->exists($resume->file_path))->toBeFalse();
});

it('hides empty sections and rows, and shows filled ones in order', function () {
    $student = resumeStudent(['gender' => 'Female', 'citizenship' => 'Filipino']);

    $html = renderResumeHtml($student);

    expect($html)->toContain('<div class="bar">Personal data</div>')
        ->toContain('Filipino')
        ->toContain("Applicant's signature")
        ->toContain('I hereby certify that the above information is true and correct to the best of my knowledge.')
        ->not->toContain('<div class="side-heading">Skills</div>')
        ->not->toContain('<div class="bar">Certificates</div>')
        ->not->toContain('<div class="bar">Projects</div>')
        ->not->toContain('<div class="bar">Experience</div>')
        ->not->toContain('<div class="bar">Character reference</div>')
        ->not->toContain('Place of birth')
        ->not->toContain('Provincial address');

    $tag = SkillTag::firstOrCreate(['name_normalized' => 'python'], ['name' => 'Python']);
    StudentSkill::create(['student_id' => $student->id, 'skill_tag_id' => $tag->id]);
    $other = SkillTag::firstOrCreate(['name_normalized' => 'public speaking'], ['name' => 'Public Speaking']);
    StudentSkill::create(['student_id' => $student->id, 'skill_tag_id' => $other->id]);
    CharacterReference::create(['student_id' => $student->id, 'name' => 'Dr. Reyes', 'position' => 'Dean', 'organization' => 'SCC', 'contact_number' => '09170000001']);

    expect(renderResumeHtml($student))
        ->toContain('<li>Python</li>')
        ->toContain('<li>Public Speaking</li>')
        ->toContain('<div class="bar">Character reference</div>')
        ->toContain('Dr. Reyes');
});

it('respects the show-to-companies toggles and never includes ID numbers', function () {
    $student = resumeStudent([
        'civil_status' => 'Single', 'show_civil_status' => false,
        'religion' => 'Catholic', 'show_religion' => true,
        'height_cm' => 160, 'show_height' => false,
        'weight_kg' => 55, 'show_weight' => true,
    ]);

    $content = app(CompanyVisibleProfile::class)->resume($student);
    $html = renderResumeHtml($student);

    expect($content['personal'])->not->toHaveKey('Civil status')
        ->not->toHaveKey('Height')
        ->toHaveKey('Religion')
        ->toHaveKey('Weight')
        ->and($html)->not->toContain('Single')
        ->not->toContain('160 cm')
        ->toContain('Catholic')
        ->toContain('55 kg')
        ->not->toMatch('/\b(SSS|TIN|NBI|Passport)\b/');
});

it('lists certificates with their details and includes older certificates without them', function () {
    $student = resumeStudent();
    $certificate = StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Cybersecurity Awareness',
        'certificate_type' => CertificateType::Seminar,
        'skill_type' => SkillType::Technical,
        'proficiency_level' => ProficiencyLevel::Intermediate,
        'issuing_organization' => 'DICT',
        'evidence_path' => 'competencies/a.pdf',
    ]);
    $certificate->learnedSkills()->create(['name' => 'Spot phishing emails']);
    Certificate::create(['student_id' => $student->id, 'title' => 'Old AWS Certificate', 'issuer' => 'Amazon', 'file_path' => 'certificates/b.pdf']);

    expect(renderResumeHtml($student))
        ->toContain('<div class="bar">Certificates</div>')
        ->toContain('Cybersecurity Awareness')
        ->toContain('Seminar')
        ->toContain('DICT')
        ->toContain('Intermediate')
        ->toContain('Technical')
        ->toContain('Spot phishing emails')
        ->toContain('Old AWS Certificate')
        ->toContain('Amazon');
});

it('generates an A4 PDF with the same layout employers download', function () {
    $student = resumeStudent(['gender' => 'Female']);
    // Smallest valid JPEG, so the photo box gets an image without needing GD.
    Storage::disk('public')->put('profiles/students/photo.jpg', base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='));
    $student->update(['profile_picture' => 'profiles/students/photo.jpg']);

    $resume = app(ResumeBuilderService::class)->build($student);

    expect($resume->content['version'])->toBe(3)
        ->and($resume->content['name'])->toBe('Ana Cruz')
        ->and(Storage::disk('private')->get($resume->file_path))->toStartWith('%PDF');
});
