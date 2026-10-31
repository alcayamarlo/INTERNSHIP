<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\InternshipApplication;
use App\Models\Portfolio;
use App\Models\RecommendationFeedback;
use App\Models\StudentCompetency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function chartData(Request $request)
    {
        $months = max(1, min(24, (int) $request->integer('months', 12)));
        $since = now()->subMonths($months - 1)->startOfMonth();

        // Coordinators only see their own institution's students; admins see everything.
        $studentIds = Auth::user()->isRole(UserRole::Coordinator)
            ? Auth::user()->coordinator?->scopedStudentsQuery()->pluck('id')
            : null;

        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', applied_at) as month"
            : 'DATE_FORMAT(applied_at, "%Y-%m") as month';

        $applicationsBase = InternshipApplication::query()
            ->when($studentIds !== null, fn ($query) => $query->whereIn('student_id', $studentIds))
            ->where('applied_at', '>=', $since);

        $applicationsPerMonth = (clone $applicationsBase)
            ->select(DB::raw($monthExpression), DB::raw('COUNT(*) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $placementStatuses = [ApplicationStatus::Accepted->value, ApplicationStatus::Completed->value];
        $placementRateByMonth = (clone $applicationsBase)
            ->select(
                DB::raw($monthExpression),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN (\''.implode("','", $placementStatuses).'\') THEN 1 ELSE 0 END) as placed')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'rate' => $row->total > 0 ? round($row->placed / $row->total * 100) : 0,
            ]);

        $placements = InternshipApplication::query()
            ->when($studentIds !== null, fn ($query) => $query->whereIn('student_id', $studentIds))
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        $competencyLevels = StudentCompetency::query()
            ->when($studentIds !== null, fn ($query) => $query->whereIn('student_id', $studentIds))
            ->select('proficiency_level', DB::raw('COUNT(*) as total'))
            ->groupBy('proficiency_level')
            ->get();

        // Skill demand and active-employer leaderboards only make sense for the
        // open market as a whole; a single coordinator's students don't change
        // which skills employers are posting, so these stay unscoped for both roles.
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

        $verifiedEvidenceQuery = fn (string $model) => $model::query()
            ->when($studentIds !== null, fn ($query) => $query->whereIn('student_id', $studentIds))
            ->where('verification_status', 'verified')
            ->count();

        $pendingReviewQuery = fn (string $model) => $model::query()
            ->when($studentIds !== null, fn ($query) => $query->whereIn('student_id', $studentIds))
            ->whereIn('verification_status', ['evidence_submitted', 'resubmitted', 'rejected'])
            ->count();

        $feedbackQuery = RecommendationFeedback::query()
            ->when($studentIds !== null, fn ($query) => $query->whereIn('student_id', $studentIds));

        $summary = [
            'total_applications' => InternshipApplication::query()
                ->when($studentIds !== null, fn ($query) => $query->whereIn('student_id', $studentIds))
                ->count(),
            'verified_evidence' => $verifiedEvidenceQuery(Portfolio::class),
            'pending_reviews' => $pendingReviewQuery(Portfolio::class),
            'helpful_feedback' => (clone $feedbackQuery)->where('feedback', 'helpful')->count(),
            'not_helpful_feedback' => (clone $feedbackQuery)->where('feedback', 'not_helpful')->count(),
        ];

        return response()->json([
            'months' => $months,
            'scoped' => $studentIds !== null,
            'applications_per_month' => $applicationsPerMonth,
            'placement_rate_by_month' => $placementRateByMonth,
            'placements' => $placements,
            'competency_levels' => $competencyLevels,
            'top_skills' => $topSkills,
            'active_employers' => $activeEmployers,
            'summary' => $summary,
        ]);
    }
}
