<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\InternshipApplication;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the coordinator dashboard with institution statistics.
     *
     * Shows student count, applications, placements, and quick overview
     * specific to the coordinator's institution.
     */
    public function index()
    {
        $coordinator = Auth::user()->coordinator;
        $studentsQuery = $coordinator->scopedStudentsQuery()->with('user');

        $students = $studentsQuery->count();

        $applications = InternshipApplication::whereIn(
            'student_id',
            $studentsQuery->pluck('id')
        )->get();

        $stats = [
            'total_students' => $students,
            'total_applications' => $applications->count(),
            'accepted' => $applications->where('status', ApplicationStatus::Accepted)->count(),
            'placement_rate' => $this->calculatePlacementRate($applications),
        ];

        $recentStudents = (clone $studentsQuery)->latest()->take(5)->get();
        $recentApplications = InternshipApplication::with(['student.user', 'internship.employer'])
            ->whereIn('student_id', $studentsQuery->pluck('id'))
            ->latest('applied_at')
            ->take(5)
            ->get();

        return view('coordinator.dashboard', [
            'coordinator' => $coordinator,
            'stats' => $stats,
            'recentStudents' => $recentStudents,
            'recentApplications' => $recentApplications,
            'notifications' => Auth::user()->appNotifications()->latest()->take(5)->get(),
        ]);
    }

    /**
     * List students in the coordinator's institution.
     */
    public function students()
    {
        $coordinator = Auth::user()->coordinator;

        return view('coordinator.students.index', [
            'students' => $coordinator->scopedStudentsQuery()
                ->with(['user', 'institution', 'competencies'])
                ->latest()
                ->paginate(15),
        ]);
    }

    /**
     * Show detailed information for a specific student.
     */
    public function showStudent(Student $student)
    {
        $this->authorize('view', $student);

        $student->load(['user', 'institution', 'competencies', 'applications.internship.employer']);

        return view('coordinator.students.show', compact('student'));
    }

    /**
     * Calculate the placement rate for applications.
     */
    private function calculatePlacementRate($applications): float
    {
        if ($applications->isEmpty()) {
            return 0;
        }

        $accepted = $applications->where('status', ApplicationStatus::Accepted)->count();
        return round(($accepted / $applications->count()) * 100, 1);
    }
}
