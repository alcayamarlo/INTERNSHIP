<?php

namespace App\Services;

use App\Models\AcademicSetting;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

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

    /** The same rules as reasons(), as a query filter on students. */
    public function constrain(Builder $students): Builder
    {
        $settings = AcademicSetting::current();

        return $students
            ->when($settings->require_fourth_year_standing, fn (Builder $query) => $query->where('year_level', '4th Year'))
            ->when($settings->require_major_subjects_passed, fn (Builder $query) => $query->where('major_subjects_passed', true));
    }
}
