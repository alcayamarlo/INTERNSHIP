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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('stores a real portfolio file and allows a coordinator to review it', function () {
    Storage::fake('private');
    Storage::fake('public');

    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Ave',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $studentUser = User::factory()->create([
        'role' => UserRole::Student,
        'email' => 'student@example.com',
    ]);

    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => $institution->id,
        'program' => 'BS Computer Science',
    ]);

    $coordinatorUser = User::factory()->create([
        'role' => UserRole::Coordinator,
        'email' => 'coordinator@example.com',
    ]);

    Coordinator::create([
        'user_id' => $coordinatorUser->id,
        'institution_id' => $institution->id,
        'department' => 'Internship Office',
    ]);

    $file = UploadedFile::fake()->create('project.pdf', 200, 'application/pdf');

    $this->actingAs($studentUser)
        ->post(route('student.portfolio.store'), [
            'title' => 'Capstone Project',
            'type' => 'project',
            'description' => 'Built a Laravel dashboard for student placements.',
            'file' => $file,
        ])
        ->assertRedirect(route('student.portfolio.index'))
        ->assertSessionHas('success', 'Portfolio item uploaded successfully!');

    $portfolio = Portfolio::query()->where('student_id', $student->id)->firstOrFail();

    expect($portfolio->file_path)->not->toBeEmpty()
        ->and(Storage::disk('private')->exists($portfolio->file_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($portfolio->file_path))->toBeFalse();

    $this->actingAs($coordinatorUser)
        ->post(route('coordinator.verification.reviewPortfolio', $portfolio), [
            'verification_status' => 'verified',
            'review_notes' => 'Accepted as valid evidence.',
        ])
        ->assertRedirect();

    $portfolio->refresh();

    expect($portfolio->verification_status)->toBe('verified')
        ->and($portfolio->review_notes)->toBe('Accepted as valid evidence.');

    Certificate::create([
        'student_id' => $student->id,
        'title' => 'Verified Student Certificate',
        'issuer' => 'Test Issuer',
        'verification_status' => 'verified',
    ]);
    $competency = StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Verified Student Skill',
        'category' => CompetencyCategory::Technical,
        'proficiency_level' => ProficiencyLevel::Advanced,
        'verification_status' => 'verified',
    ]);

    $this->actingAs($studentUser)
        ->get(route('student.portfolio.index'))
        ->assertOk()
        ->assertSee('Verified Student Certificate')
        ->assertSee('bi-patch-check-fill');

    $this->get(route('student.competencies.index'))
        ->assertOk()
        ->assertSee('Verified Student Skill')
        ->assertSee('bi-patch-check-fill');
});
