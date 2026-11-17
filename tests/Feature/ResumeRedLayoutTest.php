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
use App\Services\ResumeContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

/** A resume with `$entries` projects of `$words` words each: grows the document to test the page fitting. */
function bigResumeContent(Student $student, int $entries, int $words): array
{
    $content = app(CompanyVisibleProfile::class)->resume($student->fresh());
    $text = trim(str_repeat('Built and tested a feature with the team. ', (int) ceil($words / 8)));
    $content['projects'] = collect(range(1, $entries))->map(fn ($i) => ['title' => "Project number {$i}", 'rows' => ['Tools used' => 'Laravel, MySQL'], 'description' => $text])->all();

    return $content;
}

it('renders every section in the Appendix H layout for a student with full data', function () {
    $student = redResumeStudent([
        'program' => 'BS Information Technology', 'department' => 'College of Computer Studies', 'year_level' => '4th Year',
        'about_me' => 'Build reliable web apps.', 'city' => 'Cebu City', 'province' => 'Cebu',
        'elementary_school' => 'Cebu Central School', 'elementary_year_graduated' => '2014-2015',
    ]);
    $tag = SkillTag::firstOrCreate(['name_normalized' => 'python'], ['name' => 'Python']);
    $student->studentSkills()->create(['skill_tag_id' => $tag->id]);
    StudentCompetency::create([
        'student_id' => $student->id, 'name' => 'Cybersecurity Awareness', 'certificate_type' => CertificateType::Seminar,
        'skill_type' => SkillType::Technical, 'proficiency_level' => ProficiencyLevel::Intermediate, 'issuing_organization' => 'DICT',
    ])->learnedSkills()->create(['name' => 'Spot phishing emails']);
    $student->awards()->create(['level' => AwardLevel::College, 'title' => "Dean's Lister", 'year' => '2024']);
    Portfolio::create(['student_id' => $student->id, 'title' => 'Inventory System', 'type' => 'project', 'category' => 'project', 'tools_used' => 'Laravel', 'description' => 'Tracks stock.']);
    $student->experiences()->create(['organization' => 'Computer Shop', 'position' => 'IT Assistant', 'start_date' => '2025-06-01', 'end_date' => '2025-08-01']);
    CharacterReference::create(['student_id' => $student->id, 'name' => 'Dr. Reyes', 'position' => 'Dean', 'organization' => 'SCC', 'contact_number' => '09170000001']);

    $html = redResumeHtml($student);

    // The standard order: objective, education, skills, projects, OJT and experience, certifications, awards, references.
    // Left column (photo, About Me, Contact Me, Skills), then the right column (name, title, Education, ...).
    $order = ['class="initials">NP<', '<div class="pill">About Me</div>', 'Build reliable web apps.', '<div class="pill">Contact Me</div>', '09171234567', 'nino@example.test', 'Cebu City, Cebu',
        '<div class="pill">Skills</div>', '<li>Python</li>', '<h1 class="surname">Peña</h1>', '<p class="given">Niño</p>', '<p class="title">BS Information Technology Student</p>',
        '<div class="pill">Education</div>', '<p class="edu-level">College</p>', '<p class="edu-level">Elementary</p>', '<p class="edu-years">2014-2015</p>', '<p class="edu-school">Cebu Central School</p>',
        '<div class="pill">Projects</div>', 'Inventory System', '<div class="pill">OJT and Experience</div>', 'IT Assistant',
        '<div class="pill">Certifications</div>', 'Spot phishing emails', '<div class="pill">Honors and Awards</div>', "Dean&#039;s Lister", '<div class="pill">Character References</div>', 'Dr. Reyes'];
    $positions = array_map(fn (string $text) => strpos($html, $text), $order);
    expect($positions)->each->not->toBeFalse()->and($positions)->toBe(collect($positions)->sort()->values()->all());
    // No older layout parts, no signature block, and no "Appendix H" / "Curriculum Vitae" title: it starts at the name.
    expect($html)->not->toContain('side-heading')->not->toContain("Applicant's signature")
        ->not->toMatch('/appendix\s*h|curriculum\s+vitae/i');

    $resume = app(ResumeBuilderService::class)->build($student->fresh());
    expect(Storage::disk('private')->get($resume->file_path))->toStartWith('%PDF');
});

it('renders a student with almost no data without empty sections, rows, or placeholders', function () {
    $student = redResumeStudent();
    $student->user->update(['phone' => null]);

    $html = redResumeHtml($student);

    // Only Contact Me has something to show; no photo, so the initials circle.
    expect($html)->toContain('<h1 class="surname">Peña</h1>')
        ->toContain('nino@example.test')
        ->not->toContain('09171234567')
        ->toContain('class="initials">NP<')
        ->not->toContain('data:image/jpeg')
        ->and(substr_count($html, 'class="pill"'))->toBe(1);
    expect($html)
        ->not->toContain('null')
        ->not->toContain('N/A');
    expect(app(ResumeBuilderService::class)->build($student->fresh())->file_path)->not->toBeNull();
});

it('leaves out personal and sensitive data, however much is on the profile', function () {
    $student = redResumeStudent([
        'gender' => 'Male', 'citizenship' => 'Filipino', 'civil_status' => 'Single', 'show_civil_status' => true,
        'religion' => 'Catholic', 'show_religion' => true, 'height_feet' => 5, 'height_inches' => 3, 'show_height' => true,
        'weight_kg' => 55, 'show_weight' => true, 'date_of_birth' => '2003-03-04', 'place_of_birth' => 'Talisay City',
        'address' => '45 Osmeña Blvd', 'city' => 'Cebu City', 'province' => 'Cebu', 'zip_code' => '6000', 'provincial_address' => 'Barili, Cebu',
        'emergency_contact_name' => 'Maria Peña', 'emergency_contact_relationship' => 'Mother', 'emergency_contact_phone' => '09170000000',
        'father_name' => 'Jose Peña', 'mother_name' => 'Maria Peña',
    ]);

    $content = app(CompanyVisibleProfile::class)->resume($student->fresh());
    $html = redResumeHtml($student);

    expect($content)->not->toHaveKey('personal')->and($content['version'])->toBe(5)
        ->and($content['contact'])->toBe(['Mobile' => '09171234567', 'Email' => 'nino@example.test', 'City' => 'Cebu City, Cebu']);
    foreach (['Single', 'Catholic', '5 ft 3 in', '55 kg', 'March 4, 2003', '2003', 'Talisay', 'Osmeña Blvd', '6000', 'Barili', 'Maria Peña', 'Jose Peña', 'Mother', 'Filipino', 'Male', 'Personal data', 'Emergency'] as $private) {
        expect($html)->not->toContain($private);
    }
    expect(json_encode($content))->not->toContain('Single')->not->toContain('Talisay')->not->toContain('Osmeña');
});

it('hides contact details from a company the student has not applied to, and shows them after applying', function () {
    $student = redResumeStudent(['city' => 'Cebu City']);
    $resume = app(ResumeBuilderService::class)->build($student);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Acme', 'contact_person' => 'HR']);
    $service = app(ResumeBuilderService::class);
    $withoutContact = new ReflectionMethod($service, 'withoutContact');

    // What the company would get before applying: rendered on the fly, without contact details.
    $hiddenHtml = view('student.resume.pdf', ['content' => $withoutContact->invoke($service, $resume->content), 'photo' => null])->render();
    expect($hiddenHtml)->not->toContain('nino@example.test')->not->toContain('09171234567')->not->toContain('Cebu City');

    $before = $service->pdfResponse($resume, $employerUser, download: false);
    expect(app(CompanyVisibleProfile::class)->hasApplied($student, $employerUser))->toBeFalse()
        ->and($before->getContent())->toStartWith('%PDF')
        ->and($before->getContent())->not->toBe(Storage::disk('private')->get($resume->file_path));

    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Web Intern', 'description' => 'x', 'status' => 'open']);
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
        ->toContain('Dean&#039;s Lister, 2024')
        ->toContain('Best Thesis &lt;b&gt;2025&lt;/b&gt;')
        ->not->toContain('<b>2025</b>');
});

it('uses the burgundy accent in the exported PDF', function () {
    $student = redResumeStudent(['about_me' => 'Build reliable web apps.']);
    $resume = app(ResumeBuilderService::class)->build($student);

    // #5E010B on the name and the section rules.
    expect(pdfFillColours(Storage::disk('private')->get($resume->file_path)))->toContain('0.369 0.004 0.043');
});

it('keeps older resumes on the layout they were generated with', function () {
    $student = redResumeStudent();
    $service = app(ResumeBuilderService::class);

    foreach ([
        'resumes/old2.pdf' => ['version' => 2, 'name' => 'Niño Peña', 'personal' => ['Gender' => 'Male']],
        'resumes/old3.pdf' => ['version' => 3, 'name' => 'Niño Peña', 'contact' => ['Email' => 'nino@example.test'], 'personal' => ['Gender' => 'Male'], 'skills' => ['PHP']],
    ] as $path => $content) {
        $resume = Resume::create(['student_id' => $student->id, 'file_path' => $path, 'content' => $content, 'generated_at' => now()]);

        // The file is missing, so it is re-rendered from the stored snapshot with the layout it was made with.
        expect(Storage::disk($service->diskFor($resume))->get($path))->toStartWith('%PDF');
    }
});

// ---------------------------------------------------------------------------
// One page, two at most
// ---------------------------------------------------------------------------

it('fits a normal resume on one page, uses more room for a long one, and refuses one over two pages', function () {
    $student = redResumeStudent(['about_me' => 'Build reliable web apps.', 'city' => 'Cebu City']);
    $service = app(ResumeBuilderService::class);

    expect($service->layout(app(CompanyVisibleProfile::class)->resume($student), $student)['pages'])->toBe(1);

    // Long enough for the normal spacing to run over a page: the tighter spacing is tried before a second page is used.
    $medium = bigResumeContent($student, entries: 5, words: 60);
    $mediumLayout = $service->layout($medium, $student);
    expect($mediumLayout['pages'])->toBeLessThanOrEqual(2);

    $long = bigResumeContent($student, entries: 8, words: 120);
    $longLayout = $service->layout($long, $student);
    expect($longLayout['pages'])->toBe(2)->and($longLayout['pdf'])->toStartWith('%PDF');

    // More than two pages: refused, with a message the editor shows.
    $tooLong = bigResumeContent($student, entries: 30, words: 140);
    expect($service->layout($tooLong, $student)['pages'])->toBeGreaterThan(2);
    try {
        $service->store($student, $tooLong);
        $this->fail('A resume over two pages should be refused.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['resume'][0])->toContain('pages')->toContain('at most 2 pages');
    }
    expect(Resume::count())->toBe(0);
});

it('reports the page count of unsaved edits for the editor, rate limited', function () {
    $student = redResumeStudent(['about_me' => 'Build reliable web apps.']);
    $resume = app(ResumeBuilderService::class)->build($student);
    $this->actingAs($student->user);
    $payload = app(ResumeContent::class)->toEditor($resume->content);

    $this->postJson(route('student.resume.pages', $resume), $payload)->assertOk()->assertJson(['pages' => 1, 'limit' => 2, 'fits' => true]);

    // Someone else's resume, and a form with errors (the editor keeps its last answer).
    $this->postJson(route('student.resume.pages', $resume), [...$payload, 'name_given' => str_repeat('a', 90)])->assertStatus(422);
    $stranger = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $stranger->id]);
    $this->actingAs($stranger)->postJson(route('student.resume.pages', $resume), $payload)->assertForbidden();

    // At most 20 answers a minute: rendering a PDF is the expensive part.
    $this->actingAs($student->user);
    foreach (range(1, 25) as $i) {
        $status = $this->postJson(route('student.resume.pages', $resume), [...$payload, 'summary' => "Objective {$i}"])->getStatusCode();
    }
    expect($status)->toBe(429);
});

it('shows the 2x2 photo only while the student keeps it on', function () {
    $student = redResumeStudent();
    // Smallest valid JPEG.
    Storage::disk('private')->put('photos/2x2.jpg', base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='));
    $student->update(['id_photo_path' => 'photos/2x2.jpg']);
    $service = app(ResumeBuilderService::class);
    $content = app(CompanyVisibleProfile::class)->resume($student->fresh());

    expect($content['show_photo'])->toBeTrue();
    $withPhoto = $service->layout($content, $student->fresh());
    $without = $service->layout([...$content, 'show_photo' => false], $student->fresh());

    expect(substr_count($withPhoto['pdf'], '/Subtype /Image'))->toBeGreaterThanOrEqual(1)
        ->and(substr_count($without['pdf'], '/Subtype /Image'))->toBe(0)
        ->and($service->previewHtml([...$content, 'show_photo' => false]))->not->toContain('data:image/jpeg');
});

// ---------------------------------------------------------------------------
// Older resumes
// ---------------------------------------------------------------------------

it('opens an older resume with personal data in the editor as version 5, without the personal data', function () {
    $student = redResumeStudent(['city' => 'Cebu City', 'province' => 'Cebu']);
    $tag = SkillTag::firstOrCreate(['name_normalized' => 'python'], ['name' => 'Python']);
    $v3 = Resume::create([
        'student_id' => $student->id, 'file_path' => 'resumes/v3.pdf', 'generated_at' => now(),
        'content' => [
            'version' => 3, 'name' => 'Niño Peña', 'initials' => 'NP', 'summary' => 'My own words.', 'generated_at' => now()->toIso8601String(),
            'contact' => ['Mobile' => '09171234567', 'Email' => 'nino@example.test', 'City address' => '45 Osmeña Blvd, Cebu City', 'Provincial address' => 'Barili, Cebu'],
            'personal' => ['Birthday' => 'March 4, 2003', 'Civil status' => 'Single', 'Emergency contact' => 'Maria Peña'],
            'education' => ['Course' => 'BSIT'], 'skills' => ['Python', 'Teamwork'], 'awards' => [], 'certificates' => [], 'projects' => [], 'experiences' => [], 'references' => [],
        ],
    ]);
    $this->actingAs($student->user);

    $page = $this->get(route('student.resume.edit', $v3))->assertOk();
    $page->assertSee('value="Peña"', false)->assertSee('value="Niño"', false)->assertSee('My own words.')->assertSee('value="Python"', false)->assertSee('value="BSIT"', false)
        ->assertDontSee('Birthday')->assertDontSee('Civil status')->assertDontSee('Maria Peña')->assertDontSee('Osmeña')->assertDontSee('Barili');

    $payload = app(ResumeContent::class)->toEditor(app(ResumeContent::class)->upgrade($v3->content, $student));
    $this->putJson(route('student.resume.update', $v3), $payload)->assertOk();

    $saved = $v3->fresh()->content;
    expect($saved['version'])->toBe(5)->and($saved)->not->toHaveKey('personal')
        ->and($saved['name_last'])->toBe('Peña')->and($saved['education'][0])->toMatchArray(['level' => 'College', 'detail' => 'BSIT'])
        ->and($saved['contact'])->toBe(['Mobile' => '09171234567', 'Email' => 'nino@example.test', 'City' => 'Cebu City, Cebu'])
        ->and($saved['skills']['technical'])->toContain('Python');
});

it('rebuilds saved resumes without personal data with resumes:rebuild, and says which are too long', function () {
    $student = redResumeStudent(['about_me' => 'From the profile.', 'city' => 'Cebu City']);
    $v3 = Resume::create([
        'student_id' => $student->id, 'file_path' => 'resumes/v3.pdf', 'generated_at' => now(),
        'content' => ['version' => 3, 'name' => 'Niño Peña', 'contact' => ['Email' => 'nino@example.test'], 'personal' => ['Birthday' => 'March 4, 2003'], 'skills' => ['PHP']],
    ]);
    $v2 = Resume::create(['student_id' => $student->id, 'file_path' => 'resumes/v2.pdf', 'generated_at' => now(), 'content' => ['version' => 2, 'name' => 'Old', 'personal' => ['Gender' => 'Male']]]);
    $current = app(ResumeBuilderService::class)->build($student->fresh());

    // A dry run changes nothing.
    $this->artisan('resumes:rebuild', ['--dry-run' => true])->expectsOutputToContain('Would update 2 resume(s) (1 rebuilt from the profile). 1 already current.')->assertSuccessful();
    expect($v3->fresh()->content['version'])->toBe(3);

    $this->artisan('resumes:rebuild')->expectsOutputToContain('Updated 2 resume(s) (1 rebuilt from the profile). 1 already current.')->assertSuccessful();
    expect($v3->fresh()->content)->not->toHaveKey('personal')->and($v3->fresh()->content['version'])->toBe(5)
        ->and($v2->fresh()->content['version'])->toBe(5)->and($v2->fresh()->content['summary'])->toBe('From the profile.')
        ->and($current->fresh()->content['version'])->toBe(5)
        ->and(Storage::disk('private')->get($v3->fresh()->file_path))->toStartWith('%PDF');

    // Running it again finds nothing to do.
    $this->artisan('resumes:rebuild')->expectsOutputToContain('Updated 0 resume(s)')->assertSuccessful();
});

it('makes the 2x2 round, inside a burgundy ring, for the Appendix H layout', function () {
    $blue = imagecreatetruecolor(100, 100);
    imagefill($blue, 0, 0, imagecolorallocate($blue, 0, 0, 255));
    ob_start();
    imagejpeg($blue);
    $square = ob_get_clean();

    $round = imagecreatefromstring(app(\App\Services\ImageProcessor::class)->circle($square, 200));
    $rgb = fn (int $x, int $y): array => [($c = imagecolorat($round, $x, $y)) >> 16 & 255, $c >> 8 & 255, $c & 255];

    [$r, $g, $b] = $rgb(2, 2);       // outside the circle: white
    expect(min($r, $g, $b))->toBeGreaterThan(235);
    [$r, $g, $b] = $rgb(100, 100);   // the photo
    expect($b)->toBeGreaterThan(200)->and($r)->toBeLessThan(60);
    [$r, $g, $b] = $rgb(100, 4);     // the ring: burgundy #5E010B
    expect($r)->toBeGreaterThan(60)->toBeLessThan(130)->and($g)->toBeLessThan(40)->and($b)->toBeLessThan(50);
});
