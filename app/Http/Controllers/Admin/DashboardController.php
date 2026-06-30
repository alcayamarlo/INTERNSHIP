<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Student;
use App\Models\SystemLog;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with system-wide metrics and logs.
     *
     * Shows user statistics, activity logs, recent announcements,
     * and quick links for system administration.
     */
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'students' => Student::count(),
            'employers' => Employer::count(),
            'internships' => Internship::count(),
            'applications' => InternshipApplication::count(),
            'placements' => InternshipApplication::where('status', ApplicationStatus::Accepted)->count(),
        ];

        $recentUsers = User::latest()->take(5)->get();
        $recentLogs = SystemLog::with('user')->latest()->take(10)->get();
        $announcements = Announcement::latest()->take(5)->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentLogs' => $recentLogs,
            'announcements' => $announcements,
        ]);
    }
}
