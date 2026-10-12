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
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function verificationQueueFixture(): array
{
    $institution = Institution::create(['name' => 'Verification Queue Institute']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create([
        'user_id' => $coordinatorUser->id,
        'institution_id' => $institution->id,
    ]);
    $studentUser = User::factory()->create([
        'role' => UserRole::Student,
        'name' => 'Queue Example Student',
    ]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => $institution->id,
        'student_id_number' => 'SCC-24-00000002',
        'program' => 'BSIT',
        'department' => 'Computing',
        'year_level' => '3rd Year',
        'preferred_internship_field' => 'Software',
        'preferred_work_setup' => 'Hybrid',
        'preferred_location' => 'Cebu',
    ]);
    $competency = StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'JavaScript',
        'category' => CompetencyCategory::Technical,
        'proficiency_level' => ProficiencyLevel::Intermediate,
        'verification_status' => 'evidence_submitted',
    ]);
    $certificate = Certificate::create([
        'student_id' => $student->id,
        'title' => 'Web Development',
        'issuer' => 'Training Center',
        'verification_status' => 'evidence_submitted',
    ]);
    $portfolio = Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Capstone Project',
        'type' => 'project',
        'file_path' => 'portfolios/capstone.pdf',
        'verification_status' => 'evidence_submitted',
    ]);

    return compact('institution', 'coordinatorUser', 'coordinator', 'student', 'competency', 'certificate', 'portfolio');
}

it('groups the coordinator queue and exposes program filters from scoped students', function () {
    $fixture = verificationQueueFixture();

    $this->actingAs($fixture['coordinatorUser'])
        ->get(route('coordinator.verification.index'))
        ->assertOk()
        ->assertSee('Verification Queue')
        ->assertSee('BSIT')
        ->assertSee('Queue Example Student')
        ->assertSee('JavaScript')
        ->assertSee('Web Development')
        ->assertSee('Capstone Project')
        ->assertSee('Everything');
});

it('requires a reason for rejection and records review audit data', function () {
    $fixture = verificationQueueFixture();
    $url = route('coordinator.verification.update', [
        'type' => 'certificates',
        'id' => $fixture['certificate']->id,
    ]);

    $this->actingAs($fixture['coordinatorUser'])
        ->patchJson($url, ['status' => 'rejected', 'note' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('note');

    $this->patchJson($url, [
        'status' => 'rejected',
        'note' => 'Certificate expired.',
    ])->assertOk()
        ->assertJsonPath('item.status', 'rejected')
        ->assertJsonPath('message', 'Rejected with reason');

    $this->assertDatabaseHas('certificates', [
        'id' => $fixture['certificate']->id,
        'verification_status' => 'rejected',
        'review_notes' => 'Certificate expired.',
        'reviewed_by_user_id' => $fixture['coordinatorUser']->id,
    ]);
    expect($fixture['certificate']->fresh()->reviewed_at)->not->toBeNull();
    $this->assertDatabaseHas('verification_activities', [
        'student_id' => $fixture['student']->id,
        'item_type' => 'certificates',
        'item_id' => $fixture['certificate']->id,
        'action' => 'rejected',
        'note' => 'Certificate expired.',
        'coordinator_id' => $fixture['coordinator']->id,
    ]);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $fixture['student']->user_id,
        'type' => 'verification',
        'title' => 'Certificate Evidence Rejected',
    ]);
});

it('verifies all pending evidence in one request and serves the drawer history', function () {
    $fixture = verificationQueueFixture();

    $this->actingAs($fixture['coordinatorUser'])
        ->postJson(route('coordinator.verification.students.verify-all', $fixture['student']))
        ->assertOk()
        ->assertJsonPath('message', '3 verified')
        ->assertJsonCount(3, 'items');

    $this->assertDatabaseCount('verification_activities', 3);
    $this->assertDatabaseCount('notifications', 3);

    $this->getJson(route('coordinator.verification.students.show', $fixture['student']))
        ->assertOk()
        ->assertJsonPath('student.email', $fixture['student']->user->email)
        ->assertJsonPath('student.preferred_work_setup', 'Hybrid')
        ->assertJsonPath('progress.verified', 3)
        ->assertJsonCount(3, 'activity');
});

it('prevents coordinators from reviewing students at another institution', function () {
    $fixture = verificationQueueFixture();
    $otherInstitution = Institution::create(['name' => 'Other Verification Institute']);
    $otherStudentUser = User::factory()->create(['role' => UserRole::Student]);
    $otherStudent = Student::create([
        'user_id' => $otherStudentUser->id,
        'institution_id' => $otherInstitution->id,
        'program' => 'BSN',
    ]);
    $otherCompetency = StudentCompetency::create([
        'student_id' => $otherStudent->id,
        'name' => 'Other student competency',
        'category' => CompetencyCategory::Technical,
        'proficiency_level' => ProficiencyLevel::Intermediate,
        'verification_status' => 'evidence_submitted',
    ]);

    $this->actingAs($fixture['coordinatorUser'])
        ->patchJson(route('coordinator.verification.update', [
            'type' => 'competencies',
            'id' => $otherCompetency->id,
        ]), ['status' => 'verified'])
        ->assertForbidden();

    $this->getJson(route('coordinator.verification.students.show', $otherStudent))
        ->assertForbidden();
});

it('serves evidence through a private preview route scoped to the coordinator institution', function () {
    $fixture = verificationQueueFixture();
    Storage::fake('private');
    Storage::disk('private')->put('competencies/javascript.pdf', '%PDF-1.4 evidence');
    $fixture['competency']->update(['evidence_path' => 'competencies/javascript.pdf']);

    $this->actingAs($fixture['coordinatorUser'])
        ->get(route('coordinator.verification.evidence', [
            'type' => 'competencies',
            'id' => $fixture['competency']->id,
        ]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});
