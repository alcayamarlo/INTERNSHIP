<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\CompetencyCategory;
use App\Enums\ProficiencyLevel;
use App\Enums\PortfolioType;
use App\Enums\UserRole;
use App\Enums\WorkSetup;
use App\Models\Certificate;
use App\Models\Competency;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipRequirement;
use App\Models\Portfolio;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::create([
            'name' => 'Metro State University',
            'address' => '123 Education Ave, Manila',
            'contact_email' => 'info@msu.edu.ph',
            'contact_phone' => '+63 2 1234 5678',
            'description' => 'Leading institution for technology and business programs.',
        ]);

        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@skillbridge.test',
            'password' => 'password',
            'role' => UserRole::Admin,
            'phone' => '+63 900 000 0001',
        ]);

        $coordinatorUser = User::create([
            'name' => 'Maria Santos',
            'email' => 'coordinator@skillbridge.test',
            'password' => 'password',
            'role' => UserRole::Coordinator,
            'phone' => '+63 900 000 0002',
        ]);

        Coordinator::create([
            'user_id' => $coordinatorUser->id,
            'institution_id' => $institution->id,
            'department' => 'Internship Office',
        ]);

        $employerUser = User::create([
            'name' => 'John Reyes',
            'email' => 'employer@skillbridge.test',
            'password' => 'password',
            'role' => UserRole::Employer,
            'phone' => '+63 900 000 0003',
        ]);

        $employer = Employer::create([
            'user_id' => $employerUser->id,
            'company_name' => 'TechNova Solutions',
            'industry' => 'Information Technology',
            'description' => 'Software development and digital transformation company.',
            'website' => 'https://technova.example.com',
            'address' => '456 Business Park, Makati',
            'contact_person' => 'John Reyes',
        ]);

        $studentUser = User::create([
            'name' => 'Anna Dela Cruz',
            'email' => 'student@skillbridge.test',
            'password' => 'password',
            'role' => UserRole::Student,
            'phone' => '+63 900 000 0004',
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'institution_id' => $institution->id,
            'student_id_number' => 'MSU-2024-001',
            'program' => 'BS Information Technology',
            'year_level' => '4th Year',
            'career_objectives' => 'Seeking a software development internship to apply full-stack skills.',
            'address' => '789 Student St, Quezon City',
            'profile_completion' => 75,
        ]);

        $skills = [
            ['name' => 'PHP', 'category' => 'technical'],
            ['name' => 'Laravel', 'category' => 'technical'],
            ['name' => 'JavaScript', 'category' => 'technical'],
            ['name' => 'MySQL', 'category' => 'technical'],
            ['name' => 'Communication', 'category' => 'soft'],
            ['name' => 'Teamwork', 'category' => 'soft'],
        ];

        foreach ($skills as $skill) {
            Skill::create($skill);
        }

        $competencyData = [
            ['name' => 'PHP Development', 'category' => CompetencyCategory::Technical],
            ['name' => 'Laravel Framework', 'category' => CompetencyCategory::Technical],
            ['name' => 'Database Design', 'category' => CompetencyCategory::Technical],
            ['name' => 'Problem Solving', 'category' => CompetencyCategory::Soft],
        ];

        foreach ($competencyData as $item) {
            Competency::create([
                'name' => $item['name'],
                'category' => $item['category'],
                'is_system' => true,
            ]);
        }

        StudentCompetency::create([
            'student_id' => $student->id,
            'name' => 'PHP Development',
            'category' => CompetencyCategory::Technical,
            'description' => 'Built web applications using PHP.',
            'proficiency_level' => ProficiencyLevel::Advanced,
        ]);

        StudentCompetency::create([
            'student_id' => $student->id,
            'name' => 'Laravel Framework',
            'category' => CompetencyCategory::Technical,
            'description' => 'Developed MVC applications with Laravel.',
            'proficiency_level' => ProficiencyLevel::Intermediate,
        ]);

        StudentCompetency::create([
            'student_id' => $student->id,
            'name' => 'Communication',
            'category' => CompetencyCategory::Soft,
            'proficiency_level' => ProficiencyLevel::Advanced,
        ]);

        $internship = Internship::create([
            'employer_id' => $employer->id,
            'title' => 'Web Developer Intern',
            'description' => 'Join our development team to build modern web applications.',
            'responsibilities' => "Develop features using Laravel\nCollaborate with senior developers\nWrite clean, tested code",
            'requirements' => 'BS IT or related field, knowledge of PHP and Laravel.',
            'duration' => '3 months',
            'allowance' => 8000,
            'work_setup' => WorkSetup::Hybrid,
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
            'requirement_name' => 'Laravel Framework',
            'required_level' => ProficiencyLevel::Beginner,
        ]);

        Internship::create([
            'employer_id' => $employer->id,
            'title' => 'UI/UX Design Intern',
            'description' => 'Support our design team in creating user-friendly interfaces.',
            'duration' => '2 months',
            'allowance' => 6000,
            'work_setup' => WorkSetup::Remote,
            'location' => 'Remote',
            'status' => 'open',
        ]);

        InternshipApplication::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => ApplicationStatus::Reviewed,
            'match_percentage' => 85,
            'applied_at' => now()->subDays(3),
            'cover_letter' => 'I am excited to apply for this internship opportunity.',
        ]);

        // Add more competencies for student
        StudentCompetency::create([
            'student_id' => $student->id,
            'name' => 'JavaScript',
            'category' => CompetencyCategory::Technical,
            'description' => 'Frontend development with vanilla JavaScript and frameworks.',
            'proficiency_level' => ProficiencyLevel::Intermediate,
            'obtained_at' => now()->subMonths(6),
        ]);

        StudentCompetency::create([
            'student_id' => $student->id,
            'name' => 'MySQL Database Design',
            'category' => CompetencyCategory::Technical,
            'description' => 'Database design and SQL optimization.',
            'proficiency_level' => ProficiencyLevel::Advanced,
            'obtained_at' => now()->subMonths(8),
        ]);

        StudentCompetency::create([
            'student_id' => $student->id,
            'name' => 'AWS Cloud Services',
            'category' => CompetencyCategory::Certification,
            'description' => 'AWS Certified Cloud Practitioner (Passed)',
            'proficiency_level' => ProficiencyLevel::Intermediate,
            'obtained_at' => now()->subMonths(3),
        ]);

        StudentCompetency::create([
            'student_id' => $student->id,
            'name' => 'Team Leadership',
            'category' => CompetencyCategory::Soft,
            'description' => 'Led a team of 5 developers on capstone project.',
            'proficiency_level' => ProficiencyLevel::Advanced,
            'obtained_at' => now()->subMonths(4),
        ]);

        StudentCompetency::create([
            'student_id' => $student->id,
            'name' => 'Project Management',
            'category' => CompetencyCategory::Soft,
            'description' => 'Managed multiple projects using Agile methodologies.',
            'proficiency_level' => ProficiencyLevel::Intermediate,
        ]);

        StudentCompetency::create([
            'student_id' => $student->id,
            'name' => 'Git Version Control',
            'category' => CompetencyCategory::Technical,
            'description' => 'Git and GitHub for collaborative development.',
            'proficiency_level' => ProficiencyLevel::Advanced,
        ]);

        StudentCompetency::create([
            'student_id' => $student->id,
            'name' => 'REST API Development',
            'category' => CompetencyCategory::Technical,
            'description' => 'Building and consuming REST APIs.',
            'proficiency_level' => ProficiencyLevel::Advanced,
            'obtained_at' => now()->subMonths(5),
        ]);

        // Add portfolio items
        Portfolio::create([
            'student_id' => $student->id,
            'title' => 'E-Commerce Platform Project',
            'type' => PortfolioType::Project,
            'description' => 'Full-stack e-commerce platform built with Laravel and Vue.js. Features include product catalog, shopping cart, payment integration, and admin dashboard.',
            'file_path' => 'portfolios/' . $student->id . '/project_ecommerce.pdf',
            'created_at' => now()->subMonths(2),
        ]);

        Portfolio::create([
            'student_id' => $student->id,
            'title' => 'AWS Certified Cloud Practitioner',
            'type' => PortfolioType::Certificate,
            'description' => 'Official AWS certification demonstrating cloud computing knowledge.',
            'file_path' => 'portfolios/' . $student->id . '/cert_aws.pdf',
            'created_at' => now()->subMonths(3),
        ]);

        Certificate::create([
            'student_id' => $student->id,
            'title' => 'AWS Certified Cloud Practitioner',
            'issuer' => 'Amazon Web Services',
            'issue_date' => now()->subMonths(3)->toDateString(),
            'file_path' => 'portfolios/' . $student->id . '/cert_aws.pdf',
        ]);

        Portfolio::create([
            'student_id' => $student->id,
            'title' => 'GitHub Portfolio',
            'type' => PortfolioType::Project,
            'description' => 'Collection of personal projects showcasing coding skills and best practices.',
            'file_path' => 'portfolios/' . $student->id . '/portfolio_github.pdf',
            'created_at' => now()->subMonths(1),
        ]);

        Portfolio::create([
            'student_id' => $student->id,
            'title' => 'Capstone Project Documentation',
            'type' => PortfolioType::Project,
            'description' => 'Final year capstone project: Skill-Bridge Internship Platform. Complete documentation including architecture, database design, and implementation details.',
            'file_path' => 'portfolios/' . $student->id . '/capstone_documentation.pdf',
            'created_at' => now()->subMonths(4),
        ]);

        Portfolio::create([
            'student_id' => $student->id,
            'title' => 'Academic Transcript',
            'type' => PortfolioType::Transcript,
            'description' => 'Official academic transcript from Metro State University.',
            'file_path' => 'portfolios/' . $student->id . '/transcript.pdf',
            'created_at' => now()->subMonths(1),
        ]);

        Portfolio::create([
            'student_id' => $student->id,
            'title' => 'Recommendation Letter - Professor John Doe',
            'type' => PortfolioType::Award,
            'description' => 'Letter of recommendation from Professor John Doe, highlighting technical excellence and leadership.',
            'file_path' => 'portfolios/' . $student->id . '/recommendation_letter.pdf',
            'created_at' => now()->subWeeks(2),
        ]);

        // Recalculate profile completion
        $student->calculateProfileCompletion();
    }
}
