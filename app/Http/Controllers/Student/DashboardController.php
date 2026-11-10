<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipApplication;
use App\Services\CompetencyMatchingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        private CompetencyMatchingService $matchingService,
    ) {}

    public function index()
    {
        $student = Auth::user()->student;

        // Accepting an application opens a placement. Once the student has one, the dashboard shows
        // OJT progress per company (the same data as My OJT > Progress) instead of recommendations.
        $placements = $student->placements()
            ->with(['internship', 'employer'])
            ->withApprovedHours()
            ->withPendingHours()
            ->orderByRaw("case when status = 'active' then 0 when status = 'pending_validation' then 1 else 2 end")
            ->latest()
            ->get();

        $recommendations = $placements->isEmpty()
            ? $this->matchingService->getRecommendations($student, 5)
            : collect();

        // Placed students no longer see their applications on the dashboard.
        $recentApplications = $placements->isNotEmpty() ? collect() : Cache::remember("student_recent_apps_{$student->id}", 300, function () use ($student) {
            return InternshipApplication::with('internship.employer')
                ->where('student_id', $student->id)
                ->latest('applied_at')
                ->take(5)
                ->get();
        });

        $competencyScore = Cache::remember("student_comp_score_{$student->id}", 600, function () use ($student) {
            return (int) round($student->competencies()->get()->avg(
                fn ($item) => $item->proficiency_level->score()
            ) ?: 0);
        });

        return view('student.dashboard', [
            'student' => $student,
            'recommendations' => $recommendations,
            'placements' => $placements,
            'recentApplications' => $recentApplications,
            'competencyScore' => $competencyScore,
        ]);
    }
}
