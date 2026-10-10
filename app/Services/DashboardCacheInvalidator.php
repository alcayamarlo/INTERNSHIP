<?php

namespace App\Services;

use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardCacheInvalidator
{
    public function userChanged(User $user): void
    {
        Cache::forget('admin_dashboard_stats');
        Cache::forget('admin_recent_users');

        $student = Student::where('user_id', $user->id)->first();
        if (! $student) {
            return;
        }

        if ($student->institution_id) {
            Cache::forget("coordinator_recent_students_{$student->institution_id}");
            Cache::forget("coordinator_recent_apps_{$student->institution_id}");
        }

        $employerIds = InternshipApplication::query()
            ->join('internships', 'internship_applications.internship_id', '=', 'internships.id')
            ->where('internship_applications.student_id', $student->id)
            ->distinct()
            ->pluck('internships.employer_id');

        foreach ($employerIds as $employerId) {
            Cache::forget("employer_recent_apps_{$employerId}");
        }
    }

    public function studentChanged(Student $student): void
    {
        Cache::forget('admin_dashboard_stats');

        if ($student->institution_id) {
            Cache::forget("coordinator_recent_students_{$student->institution_id}");
            Cache::forget("coordinator_recent_apps_{$student->institution_id}");
        }
    }

    public function applicationChanged(InternshipApplication $application): void
    {
        Cache::forget('admin_dashboard_stats');
        Cache::forget("student_recent_apps_{$application->student_id}");

        $employerId = Internship::whereKey($application->internship_id)->value('employer_id');
        if ($employerId) {
            Cache::forget("employer_all_apps_{$employerId}");
            Cache::forget("employer_recent_apps_{$employerId}");
        }

        $institutionId = Student::whereKey($application->student_id)->value('institution_id');
        if ($institutionId) {
            Cache::forget("coordinator_stats_{$institutionId}");
            Cache::forget("coordinator_recent_apps_{$institutionId}");
        }
    }

    public function internshipChanged(Internship $internship): void
    {
        Cache::forget('admin_dashboard_stats');

        if ($internship->employer_id) {
            Cache::forget("employer_active_internships_{$internship->employer_id}");
        }
    }

    public function evidenceChanged(int $studentId): void
    {
        Cache::forget("student_comp_score_{$studentId}");

        $student = Student::find($studentId);
        if ($student) {
            app(CompetencyMatchingService::class)->invalidateCache($student);
        }
    }
}
