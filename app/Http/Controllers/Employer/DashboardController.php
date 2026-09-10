<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\InternshipApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Display the employer dashboard with key metrics.
     */
    public function index()
    {
        $employer = Auth::user()->employer;
        $employerId = $employer->id;

        $activeInternships = Cache::remember("employer_active_internships_{$employerId}", 300, function () use ($employer) {
            return $employer->internships()->where('status', 'open')->count();
        });

        $allApplications = Cache::remember("employer_all_apps_{$employerId}", 300, function () use ($employer) {
            return InternshipApplication::whereIn(
                'internship_id',
                $employer->internships()->pluck('id')
            )->get();
        });

        $recentApplications = Cache::remember("employer_recent_apps_{$employerId}", 300, function () use ($employer) {
            return InternshipApplication::whereHas(
                'internship',
                fn ($q) => $q->where('employer_id', $employer->id)
            )
                ->with(['student.user', 'internship'])
                ->latest('applied_at')
                ->take(5)
                ->get();
        });

        $applicationStats = [
            'total' => $allApplications->count(),
            'submitted' => $allApplications->where('status', ApplicationStatus::Submitted)->count(),
            'reviewed' => $allApplications->where('status', ApplicationStatus::Reviewed)->count(),
            'interview' => $allApplications->where('status', ApplicationStatus::Interview)->count(),
            'accepted' => $allApplications->where('status', ApplicationStatus::Accepted)->count(),
        ];

        return view('employer.dashboard', [
            'employer' => $employer,
            'activeInternships' => $activeInternships,
            'recentApplications' => $recentApplications,
            'applicationStats' => $applicationStats,
            'notifications' => Auth::user()->appNotifications()->latest()->take(5)->get(),
        ]);
    }
}
