<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\InternshipApplication;
use App\Models\Portfolio;
use App\Models\RecommendationFeedback;
use App\Models\StudentCompetency;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function chartData()
    {
        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', applied_at) as month"
            : 'DATE_FORMAT(applied_at, "%Y-%m") as month';

        $applicationsPerMonth = InternshipApplication::select(
            DB::raw($monthExpression),
            DB::raw('COUNT(*) as total')
        )
            ->where('applied_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $placements = InternshipApplication::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        $competencyLevels = DB::table('student_competencies')
            ->select('proficiency_level', DB::raw('COUNT(*) as total'))
            ->groupBy('proficiency_level')
            ->get();

        $topSkills = DB::table('internship_requirements')
            ->select('requirement_name', DB::raw('COUNT(*) as total'))
            ->groupBy('requirement_name')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        $activeEmployers = DB::table('internships')
            ->join('employers', 'internships.employer_id', '=', 'employers.id')
            ->select('employers.company_name', DB::raw('COUNT(internships.id) as total'))
            ->where('internships.status', 'open')
            ->groupBy('employers.company_name')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        $summary = [
            'total_applications' => InternshipApplication::count(),
            'verified_evidence' => StudentCompetency::where('verification_status', 'verified')->count()
                + Certificate::where('verification_status', 'verified')->count()
                + Portfolio::where('verification_status', 'verified')->count(),
            'pending_reviews' => StudentCompetency::whereIn('verification_status', ['evidence_submitted', 'rejected'])->count()
                + Certificate::whereIn('verification_status', ['evidence_submitted', 'rejected'])->count()
                + Portfolio::whereIn('verification_status', ['evidence_submitted', 'rejected'])->count(),
            'helpful_feedback' => RecommendationFeedback::where('feedback', 'helpful')->count(),
            'not_helpful_feedback' => RecommendationFeedback::where('feedback', 'not_helpful')->count(),
        ];

        return response()->json([
            'applications_per_month' => $applicationsPerMonth,
            'placements' => $placements,
            'competency_levels' => $competencyLevels,
            'top_skills' => $topSkills,
            'active_employers' => $activeEmployers,
            'summary' => $summary,
        ]);
    }
}
