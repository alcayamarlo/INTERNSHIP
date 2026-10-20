<?php

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipRequirement;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use App\Services\CompetencyMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('weights verified evidence and exposes explainable recommendation details', function () {
    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Avenue',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $studentUser = User::factory()->create([
        'role' => UserRole::Student,
    ]);

    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => $institution->id,
        'program' => 'BS Information Technology',
    ]);

    $employerUser = User::factory()->create([
        'role' => UserRole::Employer,
    ]);

    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'TechNova Solutions',
        'industry' => 'Information Technology',
        'description' => 'Software development company.',
    ]);

    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Web Developer Intern',
        'description' => 'Build modern web applications.',
        'duration' => '3 months',
        'work_setup' => 'hybrid',
        'location' => 'Makati City',
        'status' => 'open',
    ]);

    InternshipRequirement::create([
        'internship_id' => $internship->id,
        'requirement_name' => 'PHP Development',
        'required_level' => ProficiencyLevel::Intermediate,
    ]);

    InternshipRequirement::create([
        'internship_id' => $internship->id,
        'requirement_name' => 'Communication',
        'required_level' => ProficiencyLevel::Intermediate,
    ]);

    StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'PHP Development',
        'category' => CompetencyCategory::Technical,
        'description' => 'Built web applications using PHP.',
        'proficiency_level' => ProficiencyLevel::Advanced,
        'verification_status' => 'verified',
    ]);

    StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'Communication',
        'category' => CompetencyCategory::Soft,
        'description' => 'Communication skills baseline.',
        'proficiency_level' => ProficiencyLevel::Advanced,
        'verification_status' => 'rejected',
    ]);

    $recommendations = app(CompetencyMatchingService::class)->getRecommendations($student, 5);

    expect($recommendations)->toHaveCount(1)
        ->and($recommendations[0]->match_percentage)->toBe(50)
        ->and($recommendations[0]->recommendation_details)->toBeArray()
        ->and($recommendations[0]->recommendation_details['matched_requirements'])->toContain(['name' => 'PHP Development', 'required_level' => 'Intermediate'])
        ->and(collect($recommendations[0]->recommendation_details['missing_requirements'])->pluck('name'))->toContain('Communication')
        ->and(collect($recommendations[0]->recommendation_details['missing_requirements'])->firstWhere('name', 'Communication')['reason'])->toBe('not_verified')
        ->and($recommendations[0]->recommendation_details['verified_match_count'])->toBe(1);
});

it('stores student feedback for recommended internships', function () {
    $institution = Institution::create([
        'name' => 'Metro State University',
        'address' => '123 Education Avenue',
        'contact_email' => 'info@msu.edu.ph',
    ]);

    $studentUser = User::factory()->create([
        'role' => UserRole::Student,
    ]);

    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => $institution->id,
        'program' => 'BS Information Technology',
    ]);

    $employerUser = User::factory()->create([
        'role' => UserRole::Employer,
    ]);

    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'TechNova Solutions',
        'industry' => 'Information Technology',
        'description' => 'Software development company.',
    ]);

    $internship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Web Developer Intern',
        'description' => 'Build modern web applications.',
        'duration' => '3 months',
        'work_setup' => 'hybrid',
        'location' => 'Makati City',
        'status' => 'open',
    ]);

    $this->actingAs($studentUser)
        ->post(route('student.internships.recommendation-feedback', $internship), [
            'feedback' => 'helpful',
            'comment' => 'This recommendation matched my PHP skills and interests.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('recommendation_feedback', [
        'student_id' => $student->id,
        'internship_id' => $internship->id,
        'feedback' => 'helpful',
        'comment' => 'This recommendation matched my PHP skills and interests.',
    ]);
});

it('applies recommendation feedback only to its internship', function () {
    $institution = Institution::create([
        'name' => 'Feedback Test College',
        'address' => '1 Campus Way',
        'contact_email' => 'feedback@example.test',
    ]);
    $studentUser = User::factory()->create(['role' => UserRole::Student]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'institution_id' => $institution->id,
        'program' => 'BS Information Technology',
    ]);
    $employerUser = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create([
        'user_id' => $employerUser->id,
        'company_name' => 'Feedback Test Company',
        'industry' => 'Technology',
        'description' => 'Test employer.',
    ]);
    $internships = collect(['Feedback Target', 'Unrated Target'])->map(fn ($title) => Internship::create([
        'employer_id' => $employer->id,
        'title' => $title,
        'description' => 'Test opportunity.',
        'work_setup' => 'hybrid',
        'status' => 'open',
    ]));

    foreach ($internships as $internship) {
        InternshipRequirement::create([
            'internship_id' => $internship->id,
            'requirement_name' => 'PHP',
            'required_level' => ProficiencyLevel::Intermediate,
        ]);
    }

    StudentCompetency::create([
        'student_id' => $student->id,
        'name' => 'PHP',
        'category' => CompetencyCategory::Technical,
        'proficiency_level' => ProficiencyLevel::Advanced,
        'verification_status' => 'evidence_submitted',
    ]);
    \App\Models\RecommendationFeedback::create([
        'student_id' => $student->id,
        'internship_id' => $internships[0]->id,
        'feedback' => 'helpful',
    ]);

    $matcher = app(CompetencyMatchingService::class);
    expect($matcher->calculateMatch($student, $internships[0]))->toBe(5)
        ->and($matcher->calculateMatch($student, $internships[1]))->toBe(0);
});
