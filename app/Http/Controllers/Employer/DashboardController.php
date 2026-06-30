<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\InternshipApplication;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the employer dashboard with key metrics.
     *
     * Shows active internships, recent applications, applicant stats,
     * and quick links to manage internship postings.
     */
    public function index()
    {
        $employer = Auth::user()->employer;

        $activeInternships = $employer->internships()
            ->where('status', 'open')
            ->count();

        $allApplications = InternshipApplication::whereIn(
            'internship_id',
            $employer->internships()->pluck('id')
        )->get();

        $recentApplications = InternshipApplication::whereHas(
            'internship',
            fn ($q) => $q->where('employer_id', $employer->id)
        )
            ->with(['student.user', 'internship'])
            ->latest('applied_at')
            ->take(5)
            ->get();

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
