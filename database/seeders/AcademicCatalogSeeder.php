<?php

namespace Database\Seeders;

use App\Models\AcademicSetting;
use App\Models\DeploymentRequirement;
use App\Models\Major;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Database\Seeder;

class AcademicCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $deploymentRequirements = [
            ['code' => 'resume', 'name' => 'Resume', 'upload_source' => DeploymentRequirement::SOURCE_SYSTEM],
            ['code' => 'certificate-of-registration', 'name' => 'Certificate of registration (COR)', 'upload_source' => DeploymentRequirement::SOURCE_STUDENT],
            ['code' => 'parent-guardian-consent', 'name' => 'Parent or guardian consent / waiver', 'upload_source' => DeploymentRequirement::SOURCE_STUDENT],
            ['code' => 'medical-certificate', 'name' => 'Medical certificate', 'upload_source' => DeploymentRequirement::SOURCE_STUDENT],
            ['code' => 'endorsement-letter', 'name' => 'Endorsement letter', 'upload_source' => DeploymentRequirement::SOURCE_COORDINATOR],
            ['code' => 'signed-moa', 'name' => 'Signed MOA / training agreement', 'upload_source' => DeploymentRequirement::SOURCE_COORDINATOR],
        ];

        foreach ($deploymentRequirements as $index => $requirement) {
            DeploymentRequirement::query()->firstOrCreate(
                ['code' => $requirement['code']],
                [...$requirement, 'sort_order' => $index + 1, 'is_active' => true]
            );
        }

        $programs = [
            ['name' => 'BS Information Technology', 'code' => 'BSIT', 'hours' => 486, 'aliases' => ['BS Information Technology', 'Bachelor of Science in Information Technology', 'BSIT']],
            ['name' => 'BS Hospitality Management', 'code' => 'BSHM', 'hours' => 600, 'aliases' => ['BS Hospitality Management', 'Bachelor of Science in Hospitality Management', 'BSHM']],
            ['name' => 'BS Tourism Management', 'code' => 'BSTM', 'hours' => 600, 'aliases' => ['BS Tourism Management', 'Bachelor of Science in Tourism Management', 'BSTM']],
            ['name' => 'BS Business Administration', 'code' => 'BSBA', 'hours' => 600, 'aliases' => ['BS Business Administration', 'Bachelor of Science in Business Administration', 'BSBA']],
            ['name' => 'BS Management Accounting', 'code' => 'BSMA', 'hours' => 486, 'aliases' => ['BS Management Accounting', 'Bachelor of Science in Management Accounting', 'BSMA']],
        ];

        $majorNames = [
            'BSBA' => ['Marketing Management', 'Human Resource Management', 'Financial Management', 'Operations Management'],
        ];
        $aliases = [];

        foreach ($programs as $record) {
            $program = Program::query()->firstOrCreate(
                ['code' => $record['code']],
                [
                    'name' => $record['name'],
                    'required_internship_hours' => $record['hours'],
                    'required_hours_confirmed' => false,
                ]
            );
            $aliases[$program->id] = $record['aliases'];

            foreach ($majorNames[$record['code']] ?? [] as $name) {
                Major::query()->firstOrCreate(['program_id' => $program->id, 'name' => $name]);
            }
        }

        foreach ($aliases as $programId => $names) {
            $program = Program::find($programId);
            Student::query()->whereIn('program', $names)->update([
                'program_id' => $programId,
                'program' => $program->display_name,
            ]);
        }

        AcademicSetting::query()->firstOrCreate(['id' => AcademicSetting::SINGLETON_ID], [
            'student_id_pattern' => '^SCC-\\d{2}-\\d{8}$',
            'require_fourth_year_standing' => true,
            'require_major_subjects_passed' => true,
            'academic_year_start_month' => 8,
            'summer_enabled' => true,
            'current_term_override' => null,
            'first_semester_start_month' => 8,
            'first_semester_start_day' => 1,
            'first_semester_end_month' => 12,
            'first_semester_end_day' => 31,
            'second_semester_start_month' => 1,
            'second_semester_start_day' => 1,
            'second_semester_end_month' => 5,
            'second_semester_end_day' => 31,
            'summer_start_month' => 6,
            'summer_start_day' => 1,
            'summer_end_month' => 7,
            'summer_end_day' => 31,
        ]);
    }
}
