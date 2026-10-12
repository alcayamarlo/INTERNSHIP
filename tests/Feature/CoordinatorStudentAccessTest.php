<?php

use App\Enums\UserRole;
use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Models\Certificate;
use App\Models\Coordinator;
use App\Models\Institution;
use App\Models\Portfolio;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use App\Models\VerificationActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createCoordinatorForInstitution(Institution $institution): User
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

function createStudentForInstitution(Institution $institution): Student
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

it('lists students for the coordinator institution', function () {
    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Ave',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $otherInstitution = Institution::create([
        'name' => 'Other University',
        'address' => '456 Campus Rd',
        'contact_email' => 'info@other.edu.ph',
    ]);

    $coordinator = createCoordinatorForInstitution($institution);
    $visibleStudent = createStudentForInstitution($institution);
    createStudentForInstitution($otherInstitution);

    $this->actingAs($coordinator)
        ->get(route('coordinator.students.index'))
        ->assertOk()
        ->assertSee($visibleStudent->user->name)
        ->assertDontSee('Other University');
});

it('limits assigned-program students to their coordinator while retaining unassigned students', function () {
    $institution = Institution::create(['name' => 'Assigned Programs University']);
    $firstCoordinator = createCoordinatorForInstitution($institution);
    $secondCoordinator = createCoordinatorForInstitution($institution);
    $firstProgram = Program::create([
        'name' => 'Computer Science',
        'code' => 'BSCS',
        'required_internship_hours' => 486,
        'coordinator_id' => $firstCoordinator->coordinator->id,
    ]);
    $secondProgram = Program::create([
        'name' => 'Information Technology',
        'code' => 'BSIT',
        'required_internship_hours' => 486,
        'coordinator_id' => $secondCoordinator->coordinator->id,
    ]);
    $firstStudent = createStudentForInstitution($institution);
    $firstStudent->update(['program_id' => $firstProgram->id]);
    $secondStudent = createStudentForInstitution($institution);
    $secondStudent->update(['program_id' => $secondProgram->id]);
    $unassignedStudent = createStudentForInstitution($institution);
    $submission = Portfolio::create([
        'student_id' => $secondStudent->id,
        'title' => 'Other coordinator evidence',
        'type' => 'project',
        'file_path' => 'portfolios/other-coordinator.pdf',
        'verification_status' => 'evidence_submitted',
    ]);

    $this->actingAs($firstCoordinator)
        ->getJson(route('coordinator.students.index'))
        ->assertOk()
        ->assertJsonCount(2, 'students')
        ->assertJsonFragment(['id' => $firstStudent->id])
        ->assertJsonFragment(['id' => $unassignedStudent->id])
        ->assertJsonMissing(['id' => $secondStudent->id]);

    $this->get(route('coordinator.students.show', $secondStudent))
        ->assertForbidden();

    $this->patchJson(route('coordinator.verification.update', ['portfolio', $submission->id]), [
        'status' => 'verified',
    ])->assertForbidden();
});

it('flags nonmatching student IDs in the coordinator list and student detail', function () {
    app(\Database\Seeders\AcademicCatalogSeeder::class)->run();
    $institution = Institution::create(['name' => "St. Cecilia's College-Cebu, Inc."]);
    $coordinator = createCoordinatorForInstitution($institution);
    $student = createStudentForInstitution($institution);
    $student->update([
        'student_id_number' => 'MSU-2024-001',
        'institution_id' => $institution->id,
    ]);

    $this->actingAs($coordinator)
        ->getJson(route('coordinator.students.index'))
        ->assertOk()
        ->assertJsonPath('students.0.student_id_needs_correction', true);

    $this->get(route('coordinator.students.show', $student))
        ->assertOk()
        ->assertSee('ID needs correction');
});

it('searches student IDs and competency names and returns verification counts', function () {
    $institution = Institution::create(['name' => 'Search University']);
    $coordinator = createCoordinatorForInstitution($institution);
    $student = createStudentForInstitution($institution);
    $student->update([
        'student_id_number' => 'SCC-24-00000001',
        'program' => 'BSIT',
        'year_level' => '3rd Year',
        'profile_completion' => 72,
    ]);
    $competency = StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'JavaScript',
        'category' => CompetencyCategory::Technical,
        'proficiency_level' => ProficiencyLevel::Intermediate,
        'verification_status' => 'verified',
    ]);
    Certificate::create([
        'student_id' => $student->id,
        'title' => 'Web Foundations',
        'verification_status' => 'evidence_submitted',
    ]);
    Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Capstone',
        'type' => 'project',
        'file_path' => 'portfolios/capstone.pdf',
        'verification_status' => 'rejected',
    ]);

    $this->actingAs($coordinator)
        ->getJson(route('coordinator.students.index', ['search' => 'SCC-24-00000001']))
        ->assertOk()
        ->assertJsonPath('students.0.student_id_number', 'SCC-24-00000001')
        ->assertJsonPath('students.0.competencies_count', 1)
        ->assertJsonPath('students.0.verified_submissions_count', 1)
        ->assertJsonPath('students.0.total_submissions_count', 3)
        ->assertJsonPath('students.0.profile_completion', 72)
        ->assertJsonPath('summary.total', 1);

    $this->getJson(route('coordinator.students.index', ['search' => 'JavaScript']))
        ->assertOk()
        ->assertJsonPath('students.0.id', $student->id);

    $this->getJson(route('coordinator.students.index', ['search' => 'missing']))
        ->assertOk()
        ->assertJsonCount(0, 'students');
});

it('allows coordinators to view students in their institution', function () {
    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Ave',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $coordinator = createCoordinatorForInstitution($institution);
    $student = createStudentForInstitution($institution);

    $this->actingAs($coordinator)
        ->get(route('coordinator.students.show', $student))
        ->assertOk()
        ->assertSee($student->user->name);
});

it('shows read-only profile submission summaries and persisted recent verification activity', function () {
    $institution = Institution::create(['name' => 'Profile University']);
    $coordinator = createCoordinatorForInstitution($institution);
    $student = createStudentForInstitution($institution);

    StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'JavaScript',
        'category' => CompetencyCategory::Technical,
        'proficiency_level' => ProficiencyLevel::Expert,
        'verification_status' => 'verified',
    ]);
    Certificate::create([
        'student_id' => $student->id,
        'title' => 'Web Foundations',
        'issuer' => 'Open Academy',
        'verification_status' => 'evidence_submitted',
    ]);
    Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Capstone',
        'description' => 'A sample project.',
        'type' => 'project',
        'file_path' => 'portfolios/capstone.pdf',
        'verification_status' => 'rejected',
    ]);

    VerificationActivity::create([
        'student_id' => $student->id,
        'item_type' => 'competency',
        'item_id' => 1,
        'item' => 'JavaScript',
        'action' => 'verified',
        'coordinator_id' => $coordinator->coordinator->id,
    ]);

    $this->actingAs($coordinator)
        ->get(route('coordinator.students.show', $student))
        ->assertOk()
        ->assertSee('1/3 verified')
        ->assertSee('1 pending')
        ->assertSee('JavaScript')
        ->assertSee('Open Academy')
        ->assertSee('Capstone')
        ->assertSee('Recent activity')
        ->assertDontSee('Confirm reject')
        ->assertDontSee('Verify all');
});

it('provides accessible global search and mobile navigation controls', function () {
    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Ave',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $coordinator = createCoordinatorForInstitution($institution);

    $this->actingAs($coordinator)
        ->get(route('coordinator.dashboard'))
        ->assertOk()
        ->assertSee('aria-label="Search the platform"', false)
        ->assertSee('aria-controls="sidebar"', false)
        ->assertSee('aria-expanded="false"', false);
});

it('creates a resume immediately when a student requests one', function () {
    $user = User::factory()->create([
        'role' => UserRole::Student,
    ]);

    $student = Student::create([
        'user_id' => $user->id,
        'institution_id' => null,
        'program' => 'BS Computer Science',
        'year_level' => '3rd Year',
        'address' => '123 Campus St',
    ]);

    $this->actingAs($user)
        ->post(route('student.resume.generate'))
        ->assertRedirect();

    $resume = $student->fresh()->resumes()->first();

    expect($resume)->not->toBeNull()
        ->and($resume->file_path)->not->toBeEmpty()
        ->and(Storage::disk('private')->exists($resume->file_path))->toBeTrue();
});

it('forbids coordinators from viewing students outside their institution', function () {
    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Ave',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $otherInstitution = Institution::create([
        'name' => 'Other University',
        'address' => '456 Campus Rd',
        'contact_email' => 'info@other.edu.ph',
    ]);

    $coordinator = createCoordinatorForInstitution($institution);
    $otherStudent = createStudentForInstitution($otherInstitution);

    $this->actingAs($coordinator)
        ->get(route('coordinator.students.show', $otherStudent))
        ->assertForbidden();
});
