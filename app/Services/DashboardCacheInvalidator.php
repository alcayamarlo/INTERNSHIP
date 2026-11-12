<?php

namespace App\Services;

use App\Models\Coordinator;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardCacheInvalidator
{
    /**
     * Coordinator dashboard cache key. Coordinators can see different students, so each has their own
     * copy. The dashboard and the invalidation below both build keys here, so they cannot drift apart.
     */
    public static function coordinatorKey(string $name, int $coordinatorId, ?int $institutionId): string
    {
        return "coordinator_{$name}_{$coordinatorId}_{$institutionId}";
    }

    public function userChanged(User $user): void
    {
        Cache::forget('admin_dashboard_stats');
        Cache::forget('admin_recent_users');

        $student = Student::where('user_id', $user->id)->first();
        if (! $student) {
            return;
        }

        if ($student->institution_id) {
            $this->forgetCoordinators($student->institution_id, ['recent_students', 'recent_apps']);
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
            $this->forgetCoordinators($student->institution_id, ['stats', 'recent_students', 'recent_apps']);
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
            $this->forgetCoordinators((int) $institutionId, ['stats', 'recent_apps']);
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

    /** Clears the named dashboard caches of every coordinator at the institution. */
    private function forgetCoordinators(int $institutionId, array $names): void
    {
        foreach (Coordinator::where('institution_id', $institutionId)->pluck('id') as $coordinatorId) {
            foreach ($names as $name) {
                Cache::forget(self::coordinatorKey($name, (int) $coordinatorId, $institutionId));
            }
        }
    }
}
