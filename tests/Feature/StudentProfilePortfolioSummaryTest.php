<?php

use App\Enums\PortfolioType;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\DeploymentRequirement;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Models\Student;
use App\Models\User;
use App\View\Presenters\PortfolioItemPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('counts a certificate and its portfolio mirror once in the profile summary', function () {
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);

    Certificate::create([
        'student_id' => $student->id,
        'title' => 'AWS Certified Cloud Practitioner',
        'issuer' => 'Amazon Web Services',
        'file_path' => 'portfolios/'.$student->id.'/aws-certificate.pdf',
    ]);

    Portfolio::create([
        'student_id' => $student->id,
        'title' => 'AWS Certified Cloud Practitioner',
        'type' => PortfolioType::Certificate,
        'description' => 'Official AWS certification.',
        'file_path' => 'portfolios/'.$student->id.'/aws-certificate.pdf',
    ]);

    $response = $this->actingAs($user)->get(route('student.profile.edit'));

    $response->assertOk()
        ->assertSee('data-total-files="1">1', false)
        ->assertSee('data-total-certificates="1">1', false)
        ->assertSee('data-portfolio-count="1">1', false)
        ->assertSee('id="profileSaveBar"', false)
        ->assertSee('Cancel')
        ->assertSee('Save changes')
        ->assertSee('AWS Certified Cloud Practitioner')
        ->assertSee('Amazon Web Services');

    $content = $response->getContent();

    expect(substr_count($content, '<tr data-portfolio-row'))->toBe(1)
        ->and($content)->toContain('name="first_name"')
        ->and($content)->not->toContain('Edit profile')
        ->and(preg_match('/<input[^>]*name="first_name"[^>]*\bdisabled\b/i', $content))->toBe(0);
});

it('redirects the legacy portfolio url to the profile portfolio tab', function () {
    $user = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('student.portfolio.index'))
        ->assertRedirect(route('student.profile.edit').'#portfolio');

    $this->get(route('student.portfolio.index', ['search' => 'AWS', 'type' => 'certificate']))
        ->assertRedirect(route('student.profile.edit').'?search=AWS&type=certificate#portfolio');
});

it('includes generated resumes in portfolio file totals without counting them as certificates or projects', function () {
    Storage::fake('private');
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'email_verified_at' => now(),
    ]);
    $student = Student::create([
        'user_id' => $user->id,
        'institution_id' => null,
        'profile_completion' => 0,
    ]);
    $path = 'resumes/'.$student->id.'/resume.pdf';
    Storage::disk('private')->put($path, 'resume');
    Resume::create([
        'student_id' => $student->id,
        'file_path' => $path,
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('data-total-files="1">1', false)
        ->assertSee('data-total-certificates="0">0', false)
        ->assertSee('Resume');
});

it('shows missing profile and checklist steps and scores only approved deployment documents', function () {
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'name' => 'Alex Student',
        'email' => 'alex@example.test',
        'phone' => '09171234567',
    ]);
    $student = Student::create([
        'user_id' => $user->id,
        'student_id_number' => 'SCC-21-00013240',
        'program' => 'Computer Science',
        'year_level' => '3rd Year',
        'career_objectives' => 'Build useful software.',
        'address' => '1 Campus Road',
        'city' => 'Cebu City',
        'province' => 'Cebu',
        'zip_code' => '6000',
        'date_of_birth' => '2004-01-15',
    ]);
    $requirement = DeploymentRequirement::query()
        ->where('code', 'certificate-of-registration')
        ->firstOrFail();
    $student->deploymentDocuments()->create([
        'title' => $requirement->name,
        'type' => 'transcript',
        'category' => 'deployment_document',
        'deployment_requirement_id' => $requirement->id,
        'file_path' => 'portfolios/'.$student->id.'/deployment/cor.pdf',
        'verification_status' => 'approved',
    ]);

    $summary = $student->profileCompletionSummary();

    expect($summary['percentage'])->toBe(61)
        ->and($summary['remaining_steps'])->toBe(count($summary['missing_steps']))
        ->and($summary['missing_steps'])->toContain('Upload a profile photo')
        ->and($summary['missing_steps'])->not->toContain('Upload '.$requirement->name);

    $this->actingAs($user)
        ->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('aria-valuenow="61"', false)
        ->assertSee('9 steps left')
        ->assertSee('Upload a profile photo');
});

it('saves profile changes asynchronously and returns refreshed completion steps', function () {
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'phone' => '09170000000',
    ]);
    $student = Student::create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->putJson(route('student.profile.update'), [
            'first_name' => 'Alex',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '09171234567',
            'address' => '1 Campus Road',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'zip_code' => '6000',
            'student_number' => 'SCC-21-00013240',
            'career_objectives' => 'Build useful software.',
        ])
        ->assertOk()
        ->assertJsonStructure([
            'profile_completion',
            'remaining_steps',
            'missing_steps',
            'student_id_number',
        ])
        ->assertJsonPath('student_id_number', 'SCC-21-00013240');

    $summary = $student->fresh()->profileCompletionSummary();
    expect($response->json('profile_completion'))->toBe($summary['percentage'])
        ->and($response->json('remaining_steps'))->toBe($summary['remaining_steps'])
        ->and($student->fresh()->profile_completion)->toBe($summary['percentage']);
});

it('opens the tab with server validation errors and marks its tab', function () {
    $user = User::factory()->create(['role' => UserRole::Student]);
    Student::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->from(route('student.profile.edit'))
        ->put(route('student.profile.update'), [
            'first_name' => '',
            'last_name' => 'Student',
            'email' => $user->email,
            'phone' => '09171234567',
            'address' => '1 Campus Road',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'zip_code' => '6000',
        ])
        ->assertRedirect(route('student.profile.edit'))
        ->assertSessionHasErrors('first_name');

    $this->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('id="tab-personal" role="tab" aria-controls="panel-personal" aria-selected="true"', false)
        ->assertSee('id="panel-personal" role="tabpanel" aria-labelledby="tab-personal" tabindex="0"', false)
        ->assertSee('First name is required.');
});

it('renders the profile for certificate, resume, portfolio, uncategorized-default, and empty portfolios', function () {
    Storage::fake('private');

    $cases = [
        'certificate' => function (Student $student): void {
            Certificate::create([
                'student_id' => $student->id,
                'title' => 'Profile Test Certificate',
                'issuer' => 'Test Issuer',
            ]);
        },
        'resume' => function (Student $student): void {
            $path = 'resumes/'.$student->id.'/resume.pdf';
            Storage::disk('private')->put($path, 'resume');
            Resume::create([
                'student_id' => $student->id,
                'file_path' => $path,
                'generated_at' => now(),
            ]);
        },
        'portfolio' => function (Student $student): void {
            Portfolio::create([
                'student_id' => $student->id,
                'title' => 'Profile Test Project',
                'type' => PortfolioType::Project,
                'file_path' => 'portfolios/'.$student->id.'/project.pdf',
            ]);
        },
        'uncategorized-default' => function (Student $student): void {
            Portfolio::create([
                'student_id' => $student->id,
                'title' => 'Profile Test Legacy File',
                'file_path' => 'portfolios/'.$student->id.'/legacy.pdf',
            ]);
        },
        'empty' => static function (Student $student): void {},
    ];

    foreach ($cases as $case => $createItems) {
        $user = User::factory()->create(['role' => UserRole::Student]);
        $student = Student::create(['user_id' => $user->id]);
        $createItems($student);

        $response = $this->actingAs($user)->get(route('student.profile.edit'));

        $response->assertOk();

        if ($case === 'portfolio') {
            $response->assertSee('data-type="project"', false);
        }

        if ($case === 'resume') {
            $response->assertSee('data-type="resume"', false);
        }
    }
});

it('uses a safe document fallback when portfolio category and type are absent', function () {
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create(['user_id' => $user->id]);
    $item = Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Uncategorized Legacy File',
        'type' => PortfolioType::Project,
        'file_path' => 'portfolios/'.$student->id.'/legacy.pdf',
    ]);
    $attributes = $item->getAttributes();
    unset($attributes['category'], $attributes['type']);
    $item->setRawAttributes($attributes, false);

    $presented = PortfolioItemPresenter::present($item);

    expect($presented['type_key'])->toBe('document')
        ->and($presented['type_label'])->toBe('Document');
});

it('renders existing public photos and private file actions as same-origin urls', function () {
    Storage::fake('public');
    Storage::disk('public')->put('profiles/grace-cruz.jpg', 'photo');

    $user = User::factory()->create([
        'role' => UserRole::Student,
        'name' => 'Grace Cruz',
    ]);
    $student = Student::create([
        'user_id' => $user->id,
        'profile_picture' => 'profiles/grace-cruz.jpg',
    ]);
    $certificate = Certificate::create([
        'student_id' => $student->id,
        'title' => 'Relative URL Certificate',
        'file_path' => 'certificates/'.$student->id.'/certificate.pdf',
    ]);

    $response = $this->actingAs($user)->get(route('student.profile.edit'));
    $response->assertOk()
        ->assertSee('src="/storage/profiles/grace-cruz.jpg"', false)
        ->assertSee('data-preview-url="'.route('student.certificates.preview', $certificate, false).'"', false)
        ->assertSee('data-download-url="'.route('student.certificates.download', $certificate, false).'"', false);

    expect($response->getContent())
        ->not->toContain('http://localhost:8000/storage/')
        ->not->toContain('http://127.0.0.1:8000/storage/');
});

it('shows initials instead of a broken image when a profile photo is missing', function () {
    $user = User::factory()->create([
        'role' => UserRole::Student,
        'name' => 'Grace Cruz',
    ]);
    Student::create([
        'user_id' => $user->id,
        'profile_picture' => 'profiles/missing-photo.jpg',
    ]);

    $response = $this->actingAs($user)->get(route('student.profile.edit'));
    $response->assertOk()
        ->assertSee('GC')
        ->assertDontSee('/storage/profiles/missing-photo.jpg');
});
