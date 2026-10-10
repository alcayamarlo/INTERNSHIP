<?php

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Coordinator;
use App\Models\Institution;
use App\Models\Portfolio;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        ->assertSee('Pending Verification Queue')
        ->assertSee($competency->name)
        ->assertSee($certificate->title)
        ->assertSee($portfolio->title);

    $this->actingAs($coordinator)
        ->post(route('coordinator.verification.reviewCompetency', $competency), [
            'verification_status' => 'verified',
            'review_notes' => 'Strong evidence and aligned with program expectations.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('student_competencies', [
        'id' => $competency->id,
        'verification_status' => 'verified',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $student->user_id,
        'type' => 'verification',
        'title' => 'Competency Evidence Verified',
    ]);

    $this->actingAs($coordinator)
        ->post(route('coordinator.verification.reviewCertificate', $certificate), [
            'verification_status' => 'rejected',
            'review_notes' => 'Certificate cannot be verified.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('certificates', [
        'id' => $certificate->id,
        'verification_status' => 'rejected',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $student->user_id,
        'type' => 'verification',
        'title' => 'Certificate Evidence Rejected',
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
