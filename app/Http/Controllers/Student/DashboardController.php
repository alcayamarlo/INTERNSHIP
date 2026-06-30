<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipApplication;
use App\Services\CompetencyMatchingService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private CompetencyMatchingService $matchingService,
        private NotificationService $notificationService
    ) {}

    public function index()
    {
        $student = Auth::user()->student;
        $recommendations = $this->matchingService->getRecommendations($student, 5);
        $recentApplications = InternshipApplication::with('internship.employer')
            ->where('student_id', $student->id)
            ->latest('applied_at')
            ->take(5)
            ->get();

        $competencyScore = (int) round($student->competencies()->get()->avg(
            fn ($item) => $item->proficiency_level->score()
        ) ?: 0);

        return view('student.dashboard', [
            'student' => $student,
            'recommendations' => $recommendations,
            'recentApplications' => $recentApplications,
            'competencyScore' => $competencyScore,
            'notifications' => Auth::user()->appNotifications()->latest()->take(5)->get(),
            'unreadCount' => $this->notificationService->unreadCount(Auth::user()),
        ]);
    }
}
