<?php

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\DeploymentRequirement;
use App\Models\Institution;
use App\Models\Portfolio;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function deploymentRequirementStudent(Institution $institution): array
{
    $user = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'program' => 'BS Computer Science',
    ]);

    return [$user, $student];
}

function deploymentRequirementCoordinator(Institution $institution): User
{
    $user = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create([
        'user_id' => $user->id,
        'institution_id' => $institution->id,
        'department' => 'Internship Office',
    ]);

    return $user;
}

it('seeds the database-managed deployment requirements and displays the required ones to students', function () {
    Storage::fake('private');
    $institution = Institution::create([
        'name' => 'St Cecilia College',
        'address' => 'Cebu',
        'contact_email' => 'office@example.edu',
    ]);
    [$user, $student] = deploymentRequirementStudent($institution);

    // Six original requirements plus the transcript and four on-request IDs.
    expect(DeploymentRequirement::query()->count())->toBe(11)
        ->and(DeploymentRequirement::query()->required()->count())->toBe(7)
        ->and(DeploymentRequirement::query()->onRequest()->pluck('code')->all())->toBe(['nbi-clearance', 'tin-id', 'sss-id', 'passport']);

    $this->actingAs($user)
        ->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('Required documents')
        ->assertSee('Certificate of registration (COR)')
        ->assertSee('Transcript of records')
        ->assertSee('Endorsement letter')
        ->assertSee('Missing')
        // IDs only appear once an internship the student applied to requests them.
        ->assertDontSee('NBI clearance');
});

it('allows students to upload and replace their checklist documents, resetting rejected reviews', function () {
    Storage::fake('private');
    $institution = Institution::create([
        'name' => 'St Cecilia College',
        'address' => 'Cebu',
        'contact_email' => 'office@example.edu',
    ]);
    [$user, $student] = deploymentRequirementStudent($institution);
    $requirement = DeploymentRequirement::query()->where('code', 'certificate-of-registration')->firstOrFail();
    $oldFile = UploadedFile::fake()->create('old-cor.pdf', 100, 'application/pdf');
    $oldPath = $oldFile->store('portfolios/'.$student->id.'/deployment', 'private');
    $document = Portfolio::create([
        'student_id' => $student->id,
        'title' => $requirement->name,
        'type' => 'transcript',
        'category' => 'deployment_document',
        'deployment_requirement_id' => $requirement->id,
        'file_path' => $oldPath,
        'verification_status' => 'rejected',
        'review_notes' => 'Please upload a legible copy.',
    ]);

    $this->actingAs($user)
        ->post(route('student.portfolio.deployment-requirements.store', $requirement), [
            'file' => UploadedFile::fake()->create('new-cor.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('student.profile.edit').'#documents')
        ->assertSessionHasNoErrors();

    $document->refresh();
    expect($document->verification_status)->toBe('resubmitted')
        ->and($document->review_notes)->toBeNull()
        ->and($document->reviewed_at)->toBeNull()
        ->and($document->file_path)->not->toBe($oldPath)
        ->and(Storage::disk('private')->exists($document->file_path))->toBeTrue()
        ->and(Storage::disk('private')->exists($oldPath))->toBeFalse();
});

it('keeps coordinator-upload requirements read-only to students', function () {
    $institution = Institution::create([
        'name' => 'St Cecilia College',
        'address' => 'Cebu',
        'contact_email' => 'office@example.edu',
    ]);
    [$user] = deploymentRequirementStudent($institution);
    $requirement = DeploymentRequirement::query()->where('code', 'signed-moa')->firstOrFail();

    $this->actingAs($user)
        ->post(route('student.portfolio.deployment-requirements.store', $requirement), [
            'file' => UploadedFile::fake()->create('agreement.pdf', 100, 'application/pdf'),
        ])
        ->assertNotFound();
});

it('lets coordinators review documents, notifies students, and counts approved requirements', function () {
    Storage::fake('private');
    $institution = Institution::create([
        'name' => 'St Cecilia College',
        'address' => 'Cebu',
        'contact_email' => 'office@example.edu',
    ]);
    [$studentUser, $student] = deploymentRequirementStudent($institution);
    $coordinator = deploymentRequirementCoordinator($institution);
    $requirement = DeploymentRequirement::query()->where('code', 'certificate-of-registration')->firstOrFail();
    $document = $student->deploymentDocuments()->create([
        'title' => $requirement->name,
        'type' => 'transcript',
        'category' => 'deployment_document',
        'deployment_requirement_id' => $requirement->id,
        'file_path' => 'portfolios/'.$student->id.'/deployment/cor.pdf',
        'verification_status' => 'submitted',
    ]);

    $this->actingAs($coordinator)
        ->patchJson(route('coordinator.verification.update', ['portfolio', $document->id]), [
            'status' => 'rejected',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('note');

    $this->patchJson(route('coordinator.verification.update', ['portfolio', $document->id]), [
        'status' => 'rejected',
        'note' => 'Please upload a clearer scan.',
    ])->assertOk();

    $this->assertDatabaseHas('notifications', [
        'user_id' => $studentUser->id,
        'type' => 'verification',
        'title' => 'Portfolio Evidence Rejected',
    ]);
    $this->actingAs($studentUser)
        ->get(route('student.profile.edit'))
        ->assertOk()
        ->assertSee('Please upload a clearer scan.');

    $this->actingAs($coordinator)
        ->patchJson(route('coordinator.verification.update', ['portfolio', $document->id]), [
            'status' => 'approved',
        ])->assertOk();

    $completion = $student->fresh()->profileCompletionSummary();
    expect($student->fresh()->profile_completion)->toBe($completion['percentage'])
        ->and($completion['missing_steps'])->not->toContain('Upload '.$requirement->name);
});

it('allows admins to add, edit, and safely remove unused requirements', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->post(route('admin.academics.deployment-requirements.store'), [
            'name' => 'Internship insurance form',
            'upload_source' => 'student',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $requirement = DeploymentRequirement::query()->where('code', 'internship-insurance-form')->firstOrFail();
    $this->patch(route('admin.academics.deployment-requirements.update', $requirement), [
        'name' => 'Insurance form',
        'upload_source' => 'student',
        'sort_order' => 7,
        'is_active' => '1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($requirement->fresh()->name)->toBe('Insurance form')
        ->and($requirement->fresh()->sort_order)->toBe(7);

    $this->delete(route('admin.academics.deployment-requirements.destroy', $requirement))
        ->assertRedirect();
    expect(DeploymentRequirement::query()->whereKey($requirement->id)->exists())->toBeFalse();
});
