<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** A student in a program handled by one coordinator, plus a coordinator from another school. */
function validationAlertFixture(): array
{
    $institution = Institution::create(['name' => 'SCC']);
    $coordinatorUser = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator = Coordinator::create(['user_id' => $coordinatorUser->id, 'institution_id' => $institution->id]);
    $program = Program::create(['name' => 'BS Information Technology', 'code' => 'BSIT', 'coordinator_id' => $coordinator->id, 'required_internship_hours' => 486]);
    $studentUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Ana Cruz']);
    $student = Student::create([
        'user_id' => $studentUser->id, 'institution_id' => $institution->id, 'program_id' => $program->id,
        'program' => 'BSIT', 'student_id_number' => 'SCC-24-00000077', 'year_level' => '4th Year',
    ]);

    $otherUser = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $otherUser->id, 'institution_id' => Institution::create(['name' => 'Other School'])->id]);

    return compact('coordinatorUser', 'studentUser', 'student', 'otherUser');
}

it('notifies the student\'s coordinator when a document is submitted, naming the student and linking to the record', function () {
    Storage::fake('private');
    ['coordinatorUser' => $coordinatorUser, 'studentUser' => $studentUser, 'student' => $student, 'otherUser' => $otherUser] = validationAlertFixture();

    $this->actingAs($studentUser)->post(route('student.portfolio.store'), [
        'title' => 'Capstone Project',
        'type' => 'project',
        'file' => UploadedFile::fake()->create('project.pdf', 50, 'application/pdf'),
    ])->assertRedirect();

    $alert = AppNotification::where('user_id', $coordinatorUser->id)
        ->where('type', NotificationService::TYPE_VALIDATION_NEEDED)
        ->sole();
    expect($alert->title)->toBe('Submission to validate')
        ->and($alert->message)->toContain('Ana Cruz (SCC-24-00000077)')
        ->and($alert->message)->toContain('Capstone Project')
        ->and($alert->data['student_id'])->toBe($student->id)
        // A coordinator from another school is not told.
        ->and(AppNotification::where('user_id', $otherUser->id)->exists())->toBeFalse();

    // Opening it goes straight to that student's record.
    $this->actingAs($coordinatorUser)
        ->get(route('notifications.show', $alert))
        ->assertRedirect(route('coordinator.students.show', $student));
    expect($alert->fresh()->read_at)->not->toBeNull();
});

it('notifies the coordinator when a certificate is uploaded', function () {
    Storage::fake('private');
    ['coordinatorUser' => $coordinatorUser, 'studentUser' => $studentUser] = validationAlertFixture();

    $this->actingAs($studentUser)->post(route('student.certificates.store'), [
        'title' => 'Laravel Basics',
        'issuer' => 'Training Center',
        'file' => UploadedFile::fake()->create('cert.pdf', 50, 'application/pdf'),
    ])->assertRedirect();

    expect(AppNotification::where('user_id', $coordinatorUser->id)
        ->where('type', NotificationService::TYPE_VALIDATION_NEEDED)
        ->where('message', 'like', '%a certificate ("Laravel Basics")%')
        ->exists())->toBeTrue();
});

it('notifies the coordinator when an accepted application opens a placement to validate', function () {
    ['coordinatorUser' => $coordinatorUser, 'student' => $student] = validationAlertFixture();
    $employer = Employer::create(['user_id' => User::factory()->create(['role' => UserRole::Employer])->id, 'company_name' => 'Acme Corp', 'verified_at' => now()]);
    $internship = Internship::create(['employer_id' => $employer->id, 'title' => 'Web Intern', 'description' => 'x', 'status' => 'open']);
    $application = InternshipApplication::create(['internship_id' => $internship->id, 'student_id' => $student->id, 'status' => ApplicationStatus::Accepted, 'match_percentage' => 0, 'applied_at' => now()]);

    $placement = app(PlacementService::class)->openForApplication($application);
    app(PlacementService::class)->openForApplication($application); // Calling it again does not notify twice.

    $alert = AppNotification::where('user_id', $coordinatorUser->id)->where('title', 'Placement to validate')->sole();
    expect($alert->message)->toContain('Ana Cruz (SCC-24-00000077) was accepted by Acme Corp for Web Intern')
        ->and($alert->data)->toMatchArray(['student_id' => $student->id, 'placement_id' => $placement->id]);
});

it('links other coordinator notifications about a student to the student record', function () {
    ['coordinatorUser' => $coordinatorUser, 'student' => $student] = validationAlertFixture();
    $notice = app(NotificationService::class)->send($coordinatorUser, 'profile_change_request', 'Student profile change request', 'Ana Cruz requested a change.', ['student_id' => $student->id], email: false);

    $this->actingAs($coordinatorUser)
        ->get(route('notifications.show', $notice))
        ->assertOk()
        ->assertSee(route('coordinator.students.show', $student), false);
});
