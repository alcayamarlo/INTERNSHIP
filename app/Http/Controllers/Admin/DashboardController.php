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
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with system-wide metrics and logs.
     */
    public function index()
    {
        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return [
                'users' => User::count(),
                'students' => Student::count(),
                'employers' => Employer::count(),
                'internships' => Internship::count(),
                'applications' => InternshipApplication::count(),
                'placements' => InternshipApplication::where('status', ApplicationStatus::Accepted)->count(),
            ];
        });

        $recentUsers = Cache::remember('admin_recent_users', 300, function () {
            return User::latest()->take(5)->get();
        });

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
