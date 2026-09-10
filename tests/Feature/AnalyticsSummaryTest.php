<?php

use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Portfolio;
use App\Models\RecommendationFeedback;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns summary analytics for applications, verification, and recommendation feedback', function () {
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

    $secondInternship = Internship::create([
        'employer_id' => $employer->id,
        'title' => 'Data Analyst Intern',
        'description' => 'Analyze platform data and reporting outcomes.',
        'duration' => '3 months',
        'work_setup' => 'remote',
        'location' => 'Quezon City',
        'status' => 'open',
    ]);

    InternshipApplication::create([
        'internship_id' => $internship->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'match_percentage' => 88,
        'applied_at' => now(),
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
        'proficiency_level' => ProficiencyLevel::Intermediate,
        'verification_status' => 'pending',
    ]);

    Certificate::create([
        'student_id' => $student->id,
        'title' => 'CCNA',
        'issuer' => 'Cisco',
        'issue_date' => now()->subMonths(2),
        'file_path' => 'portfolios/'.$student->id.'/ccna-certificate.pdf',
        'verification_status' => 'pending',
    ]);

    Portfolio::create([
        'student_id' => $student->id,
        'title' => 'Portfolio Sample',
        'type' => 'project',
        'description' => 'Project showcase',
        'file_path' => 'portfolios/'.$student->id.'/sample-portfolio.pdf',
        'verification_status' => 'rejected',
    ]);

    RecommendationFeedback::create([
        'student_id' => $student->id,
        'internship_id' => $internship->id,
        'feedback' => 'helpful',
        'comment' => 'Strong match.',
    ]);

    RecommendationFeedback::create([
        'student_id' => $student->id,
        'internship_id' => $secondInternship->id,
        'feedback' => 'not_helpful',
        'comment' => 'Not the right fit.',
    ]);

    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->getJson(route('analytics.charts'))
        ->assertOk()
        ->assertJsonPath('summary.total_applications', 1)
        ->assertJsonPath('summary.verified_evidence', 1)
        ->assertJsonPath('summary.pending_reviews', 1)
        ->assertJsonPath('summary.helpful_feedback', 1)
        ->assertJsonPath('summary.not_helpful_feedback', 1);
});
