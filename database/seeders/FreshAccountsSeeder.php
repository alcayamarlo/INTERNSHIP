<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Four starting accounts (admin, coordinator, employer, student) on @skillbridge.test with the
 * password "password". Safe to run again: each account and profile is matched by email or user
 * and updated in place, so nothing is duplicated. It does not create demo internships or OJT data.
 *
 * php artisan db:seed --class=FreshAccountsSeeder
 */
class FreshAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Production: skipped. These accounts use a known password.');

            return;
        }

        $institution = Institution::firstOrCreate(
            ['name' => "St. Cecilia's College-Cebu, Inc."],
            ['address' => 'Cebu South National Highway, Ward II, Minglanilla, Cebu'],
        );

        $account = function (string $email, string $name, UserRole $role, string $phone): User {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'role' => $role,
                'phone' => $phone,
            ]);
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

            return $user;
        };

        $account('admin@skillbridge.test', 'System Administrator', UserRole::Admin, '+63 900 000 0001');

        $coordinatorUser = $account('coordinator@skillbridge.test', 'Maria Santos', UserRole::Coordinator, '+63 900 000 0002');
        Coordinator::updateOrCreate(['user_id' => $coordinatorUser->id], [
            'institution_id' => $institution->id,
            'department' => 'Internship Office',
        ]);

        $employerUser = $account('employer@skillbridge.test', 'John Reyes', UserRole::Employer, '+63 900 000 0003');
        $employer = Employer::updateOrCreate(['user_id' => $employerUser->id], [
            'company_name' => 'TechNova Solutions',
            'industry' => 'Information Technology',
            'description' => 'Software development and digital transformation company.',
            'website' => 'https://technova.example.com',
            'address' => '456 Business Park, Makati',
            'contact_person' => 'John Reyes',
        ]);
        $employer->forceFill(['verified_at' => $employer->verified_at ?? now()])->save();

        $studentUser = $account('student@skillbridge.test', 'Anna Dela Cruz', UserRole::Student, '+63 900 000 0004');
        $program = Program::where('code', 'BSIT')->first();
        Student::updateOrCreate(['user_id' => $studentUser->id], [
            'institution_id' => $institution->id,
            'student_id_number' => 'SCC-24-00000001',
            'program_id' => $program?->id,
            'program' => $program ? $program->name.' ('.$program->code.')' : 'BS Information Technology (BSIT)',
            'year_level' => '4th Year',
            'address' => '789 Student St, Quezon City',
        ]);

        $this->command?->info('Seeded admin, coordinator, employer and student @skillbridge.test (password: password).');
    }
}
