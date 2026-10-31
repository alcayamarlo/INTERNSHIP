<?php

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Coordinator;
use App\Models\DeploymentRequirement;
use App\Models\Institution;
use App\Models\Portfolio;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createCoordinatorForVerification(Institution $institution): User
{
    $user = User::factory()->create([
        'role' => UserRole::Coordinator,
    ]);

    Coordinator::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'department' => 'Internship Office',
    ]);

    return $user;
}

function createStudentForVerification(Institution $institution): Student
{
    $user = User::factory()->create([
        'role' => UserRole::Student,
    ]);

    return Student::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'program' => 'BS Computer Science',
    ]);
}

it('shows pending evidence for coordinators and allows reviewing it', function () {
    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Ave',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $coordinator = createCoordinatorForVerification($institution);
    $student = createStudentForVerification($institution);

    // Competencies and certificates still get uploaded, but no longer appear in the coordinator review queue.
    $competency = StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Python Fundamentals',
        'category' => CompetencyCategory::Technical,
        'description' => 'Completed Python training.',
        'proficiency_level' => ProficiencyLevel::Intermediate,
        'obtained_at' => '2025-01-15',
        'verification_status' => 'evidence_submitted',
        'evidence_path' => 'competencies/1/sample.pdf',
        'evidence_name' => 'sample.pdf',
    ]);
    $certificate = Certificate::create([
        'student_id' => $student->id,
        'title' => 'Google IT Support',
        'issuer' => 'Google',
        'issue_date' => '2025-02-07',
        'file_path' => 'certificates/1/sample.pdf',
        'verification_status' => 'evidence_submitted',
    ]);

    $portfolio = Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Capstone Portfolio',
        'type' => 'project',
        'description' => 'Upload of capstone project output.',
        'file_path' => 'portfolios/1/sample.pdf',
        'verification_status' => 'evidence_submitted',
    ]);

    $this->actingAs($coordinator)
        ->get(route('coordinator.verification.index'))
        ->assertOk()
        ->assertSee('Verification Queue')
        ->assertSee($portfolio->title)
        ->assertDontSee($competency->name)
        ->assertDontSee($certificate->title);

    // Neither competencies nor certificates can be reviewed at all anymore; both routes are gone.
    expect(\Illuminate\Support\Facades\Route::has('coordinator.verification.reviewCompetency'))->toBeFalse()
        ->and(\Illuminate\Support\Facades\Route::has('coordinator.verification.reviewCertificate'))->toBeFalse();
    $this->assertDatabaseHas('student_competencies', [
        'id' => $competency->id,
        'verification_status' => 'evidence_submitted',
    ]);
    $this->assertDatabaseHas('certificates', [
        'id' => $certificate->id,
        'verification_status' => 'evidence_submitted',
    ]);

    $this->actingAs($coordinator)
        ->post(route('coordinator.verification.reviewPortfolio', $portfolio), [
            'verification_status' => 'verified',
            'review_notes' => 'Portfolio submission meets requirements.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('portfolios', [
        'id' => $portfolio->id,
        'verification_status' => 'verified',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $student->user_id,
        'type' => 'verification',
        'title' => 'Portfolio Evidence Verified',
    ]);
});

it('returns resubmitted portfolio evidence as pending and labels it for coordinator refreshes', function () {
    $institution = Institution::create(['name' => 'Resubmission Test Institution']);
    $coordinator = createCoordinatorForVerification($institution);
    $student = createStudentForVerification($institution);
    $portfolio = Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Updated project',
        'type' => 'project',
        'file_path' => 'portfolios/1/replacement.pdf',
        'verification_status' => 'resubmitted',
    ]);

    $this->actingAs($coordinator)
        ->getJson(route('coordinator.verification.index'))
        ->assertOk()
        ->assertJsonPath('students.0.items.0.id', $portfolio->id)
        ->assertJsonPath('students.0.items.0.status', 'pending')
        ->assertJsonPath('students.0.items.0.status_label', 'Resubmitted');
});

it('lets coordinators upload approved deployment documents only for students in their institution', function () {
    Storage::fake('private');
    $institution = Institution::create([
        'name' => 'St Cecilia College Cebu',
        'address' => 'Cebu',
        'contact_email' => 'office@example.edu',
    ]);
    $coordinator = createCoordinatorForVerification($institution);
    $student = createStudentForVerification($institution);

    $response = $this->actingAs($coordinator)->postJson(
        route('coordinator.students.deployment-documents.store', $student),
        [
            'deployment_requirement_id' => DeploymentRequirement::query()->where('code', 'signed-moa')->value('id'),
            'file' => UploadedFile::fake()->create('agreement.pdf', 100, 'application/pdf'),
        ]
    );
    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Deployment document uploaded.');

    $document = Portfolio::query()->where('student_id', $student->id)->firstOrFail();
    expect($document->category->value)->toBe('deployment_document')
        ->and($document->verification_status)->toBe('approved')
        ->and($document->deployment_requirement_id)->toBe(DeploymentRequirement::query()->where('code', 'signed-moa')->value('id'))
        ->and($document->uploaded_by_user_id)->toBe($coordinator->id)
        ->and(Storage::disk('private')->exists($document->file_path))->toBeTrue();

    $this->actingAs($coordinator)
        ->get(route('coordinator.verification.download', ['portfolio', $document->id]))
        ->assertOk();
});
