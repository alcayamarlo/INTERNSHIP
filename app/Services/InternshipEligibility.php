<?php

namespace App\Services;

use App\Models\AcademicSetting;
use App\Models\Student;

class InternshipEligibility
{
    public function reasons(Student $student): array
    {
        $settings = AcademicSetting::current();
        $reasons = [];

        if ($settings->require_fourth_year_standing && $student->year_level !== '4th Year') {
            $reasons[] = 'You must be in 4th Year to apply.';
        }

        if ($settings->require_major_subjects_passed && ! $student->major_subjects_passed) {
            $reasons[] = 'Your coordinator must confirm that all major subjects are passed.';
        }

        return $reasons;
    }
}
