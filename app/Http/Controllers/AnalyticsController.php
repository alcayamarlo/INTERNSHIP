<?php

namespace App\Http\Controllers;

use App\Models\InternshipApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function chartData()
    {
        $applicationsPerMonth = InternshipApplication::select(
            DB::raw('DATE_FORMAT(applied_at, "%Y-%m") as month'),
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

        return response()->json([
            'applications_per_month' => $applicationsPerMonth,
            'placements' => $placements,
            'competency_levels' => $competencyLevels,
            'top_skills' => $topSkills,
            'active_employers' => $activeEmployers,
        ]);
    }
}
