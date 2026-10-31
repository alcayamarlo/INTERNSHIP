<?php

use App\Enums\ApplicationStatus;
use App\Enums\AwardLevel;
use App\Enums\CertificateType;
use App\Enums\ProficiencyLevel;
use App\Enums\SkillType;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\CharacterReference;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Models\SkillTag;
use App\Models\Student;
use App\Models\StudentCompetency;
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

function redResumeStudent(array $attributes = []): Student
{
    $user = User::factory()->create(['role' => UserRole::Student, 'name' => 'Niño Peña', 'phone' => '09171234567', 'email' => 'nino@example.test']);

    return Student::create(['user_id' => $user->id, 'privacy_consent' => true, 'privacy_consent_at' => now(), ...$attributes]);
}

function redResumeHtml(Student $student): string
{
    return view('student.resume.pdf', [
        'content' => app(CompanyVisibleProfile::class)->resume($student->fresh()),
        'photo' => null,
    ])->render();
}

/** Fill colours used inside the PDF's (compressed) page streams. */
function pdfFillColours(string $pdf): array
{
    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
    $colours = [];
    foreach ($streams[1] as $stream) {
        $decoded = @gzuncompress($stream);
        if ($decoded && preg_match_all('/([0-9.]+ [0-9.]+ [0-9.]+) rg/', $decoded, $matches)) {
            $colours = [...$colours, ...$matches[1]];
        }
    }

    return array_values(array_unique($colours));
}

it('renders every section for a student with full data', function () {
    $student = redResumeStudent([
        'program' => 'BS Information Technology', 'department' => 'College of Computer Studies', 'year_level' => '4th Year',
        'career_objectives' => 'Build reliable web apps.', 'gender' => 'Male', 'citizenship' => 'Filipino',
        'address' => '45 Osmeña Blvd', 'city' => 'Cebu City', 'provincial_address' => 'Barili, Cebu',
        'elementary_school' => 'Cebu Central School', 'elementary_year_graduated' => 2015,
        'emergency_contact_name' => 'Maria Peña', 'emergency_contact_relationship' => 'Mother', 'emergency_contact_phone' => '09170000000',
    ]);
    $tag = SkillTag::firstOrCreate(['name_normalized' => 'python'], ['name' => 'Python']);
    $student->studentSkills()->create(['skill_tag_id' => $tag->id]);
    StudentCompetency::create([
        'student_id' => $student->id, 'name' => 'Cybersecurity Awareness', 'certificate_type' => CertificateType::Seminar,
        'skill_type' => SkillType::Technical, 'proficiency_level' => ProficiencyLevel::Intermediate, 'issuing_organization' => 'DICT',
    ])->learnedSkills()->create(['name' => 'Spot phishing emails']);
    $student->awards()->create(['level' => AwardLevel::College, 'title' => "Dean's Lister", 'year' => '2024']);
    Portfolio::create(['student_id' => $student->id, 'title' => 'Inventory System', 'type' => 'project', 'category' => 'project', 'tools_used' => 'Laravel', 'project_url' => 'https://example.test/inventory']);
    $student->experiences()->create(['organization' => 'Computer Shop', 'position' => 'IT Assistant', 'start_date' => '2025-06-01', 'end_date' => '2025-08-01']);
    CharacterReference::create(['student_id' => $student->id, 'name' => 'Dr. Reyes', 'position' => 'Dean', 'organization' => 'SCC', 'contact_number' => '09170000001']);

    $html = redResumeHtml($student);

    expect($html)
        ->toContain('<h1 class="name">Niño Peña</h1>')
        ->toContain('Build reliable web apps.')
        ->toContain('<div class="side-heading">Contact</div>')
        ->toContain('nino@example.test')
        ->toContain('Barili, Cebu')
        ->toContain('<div class="side-heading">Education</div>')
        ->toContain('Cebu Central School (2015)')
        ->toContain('<div class="side-heading">Skills</div>')
        ->toContain('<div class="bar">Personal data</div>')
        ->toContain('Maria Peña')
        ->toContain('<div class="bar">Certificates</div>')
        ->toContain('<li>Spot phishing emails</li>')
        ->toContain('<div class="bar">Honors / awards</div>')
        ->toContain('<div class="bar">Projects</div>')
        ->toContain('https:/&#8203;/&#8203;example.test/&#8203;inventory')
        ->toContain('<div class="bar">Experience</div>')
        ->toContain('<div class="bar">Character reference</div>')
        ->toContain("Applicant's signature")
        ->toContain('(Philippine time)')
        ->toContain('print-color-adjust: exact');

    $resume = app(ResumeBuilderService::class)->build($student->fresh());
    expect(Storage::disk('private')->get($resume->file_path))->toStartWith('%PDF');
});

it('renders a student with almost no data without empty sections, rows, or placeholders', function () {
    $student = redResumeStudent();
    $student->user->update(['phone' => null]);

    $html = redResumeHtml($student);

    expect($html)->toContain('<h1 class="name">Niño Peña</h1>')
        ->toContain('<span class="photo-initials">NP</span>')
        ->toContain('<div class="side-heading">Contact</div>')
        ->not->toContain('Mobile')
        ->not->toContain('<div class="side-heading">Education</div>')
        ->not->toContain('<div class="side-heading">Skills</div>')
        ->not->toContain('<div class="bar">')
        ->not->toContain('null')
        ->not->toContain('N/A');
    expect(app(ResumeBuilderService::class)->build($student->fresh())->file_path)->not->toBeNull();
});

it('shows sensitive details only when "Show to companies" is on, and never ID numbers', function () {
    $student = redResumeStudent([
        'civil_status' => 'Single', 'show_civil_status' => false,
        'religion' => 'Catholic', 'show_religion' => true,
        'height_cm' => 160, 'show_height' => false,
        'weight_kg' => 55, 'show_weight' => true,
    ]);

    expect(redResumeHtml($student))
        ->not->toContain('Single')
        ->not->toContain('160 cm')
        ->toContain('Catholic')
        ->toContain('55 kg')
        ->not->toMatch('/\b(SSS|TIN|NBI|Passport)\b/');
});

it('hides contact details from a company the student has not applied to, and shows them after applying', function () {
    $student = redResumeStudent(['address' => '45 Osmeña Blvd']);
    $resume = app(ResumeBuilderService::class)->build($student);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Acme', 'contact_person' => 'HR']);
    $service = app(ResumeBuilderService::class);
    $render = new ReflectionMethod($service, 'render');
    $withoutContact = new ReflectionMethod($service, 'withoutContact');

    // What the company would get before applying: rendered on the fly, without contact details.
    $hiddenHtml = view('student.resume.pdf', ['content' => $withoutContact->invoke($service, $resume->content), 'photo' => null])->render();
    expect($hiddenHtml)->not->toContain('nino@example.test')
        ->not->toContain('09171234567')
        ->not->toContain('Osmeña Blvd')
        ->not->toContain('<div class="side-heading">Contact</div>');

    $before = $service->pdfResponse($resume, $employerUser, download: false);
    expect(app(CompanyVisibleProfile::class)->hasApplied($student, $employerUser))->toBeFalse()
        ->and($before->getContent())->toStartWith('%PDF')
        ->and($before->getContent())->not->toBe(Storage::disk('private')->get($resume->file_path));

    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Web Intern', 'description' => 'x', 'work_setup' => 'remote', 'status' => 'open']);
    InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'resume_id' => $resume->id, 'status' => ApplicationStatus::Applied, 'match_percentage' => 0, 'applied_at' => now()]);

    // After applying, the company gets the stored file, which includes contact details.
    $after = $service->pdfResponse($resume, $employerUser, download: false);
    expect(app(CompanyVisibleProfile::class)->hasApplied($student, $employerUser))->toBeTrue()
        ->and($after)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
    expect(view('student.resume.pdf', ['content' => $resume->content, 'photo' => null])->render())->toContain('nino@example.test');
});

it('still renders older certificates and the old honors paragraph', function () {
    $student = redResumeStudent();
    Certificate::create(['student_id' => $student->id, 'title' => 'Old AWS Certificate', 'issuer' => 'Amazon', 'file_path' => 'certificates/b.pdf']);
    $student->awards()->create(['level' => AwardLevel::Other, 'title' => "College\r\n\r\nDean's Lister, 2024\r\nBest Thesis <b>2025</b>"]);

    $html = redResumeHtml($student);

    expect($html)->toContain('Old AWS Certificate')
        ->toContain('Amazon')
        ->toContain('<p class="award-line">Dean&#039;s Lister, 2024</p>')
        ->toContain('<p class="award-line">Best Thesis &lt;b&gt;2025&lt;/b&gt;</p>')
        ->not->toContain('<b>2025</b>');
});

it('keeps the red sidebar and header colours in the exported PDF', function () {
    $student = redResumeStudent(['gender' => 'Male']);
    $resume = app(ResumeBuilderService::class)->build($student);

    $colours = pdfFillColours(Storage::disk('private')->get($resume->file_path));

    // #B3001B sidebar and #E31B2D photo box / header bars.
    expect($colours)->toContain('0.702 0.000 0.106')
        ->toContain('0.890 0.106 0.176');
});

it('keeps older resumes on the layout they were generated with', function () {
    $student = redResumeStudent();
    $resume = Resume::create([
        'student_id' => $student->id,
        'file_path' => 'resumes/old.pdf',
        'content' => ['version' => 2, 'name' => 'Niño Peña', 'personal' => ['Gender' => 'Male']],
        'generated_at' => now(),
    ]);

    // The file is missing, so it is re-rendered from the stored snapshot with the bio-data layout.
    $disk = app(ResumeBuilderService::class)->diskFor($resume);
    expect(Storage::disk($disk)->get('resumes/old.pdf'))->toStartWith('%PDF');
});
