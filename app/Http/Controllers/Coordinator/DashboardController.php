<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\InternshipApplication;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the coordinator dashboard with institution statistics.
     */
    public function index()
    {
        $coordinator = Auth::user()->coordinator;
        $institutionId = $coordinator->institution_id;

        $stats = Cache::remember("coordinator_stats_{$institutionId}", 300, function () use ($coordinator, $institutionId) {
            $studentIds = $coordinator->scopedStudentsQuery()->pluck('id');

            $applications = InternshipApplication::whereIn('student_id', $studentIds)->get();

            return [
                'total_students' => $studentIds->count(),
                'total_applications' => $applications->count(),
                'accepted' => $applications->where('status', ApplicationStatus::Accepted)->count(),
                'placement_rate' => $this->calculatePlacementRate($applications),
            ];
        });

        $recentStudents = Cache::remember("coordinator_recent_students_{$institutionId}", 300, function () use ($coordinator) {
            return $coordinator->scopedStudentsQuery()->with('user')->latest()->take(5)->get();
        });

        $recentApplications = Cache::remember("coordinator_recent_apps_{$institutionId}", 300, function () use ($coordinator) {
            $studentIds = $coordinator->scopedStudentsQuery()->pluck('id');
            return InternshipApplication::with(['student.user', 'internship.employer'])
                ->whereIn('student_id', $studentIds)
                ->latest('applied_at')
                ->take(5)
                ->get();
        });

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
     * Batch verify competencies, certificates, or portfolios.
     */
    public function batchVerify(Request $request)
    {
        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator && $coordinator->institution_id, 403, 'No institution assigned.');

        $validated = $request->validate([
            'type' => ['required', 'in:competency,certificate,portfolio'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'verification_status' => ['required', 'in:verified,rejected'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $modelClass = match ($validated['type']) {
            'competency' => \App\Models\StudentCompetency::class,
            'certificate' => \App\Models\Certificate::class,
            'portfolio' => \App\Models\Portfolio::class,
        };

        DB::transaction(function () use ($modelClass, $validated, $coordinator) {
            $items = $modelClass::whereIn('id', $validated['ids'])
                ->whereIn('verification_status', ['evidence_submitted', 'rejected'])
                ->get();

            foreach ($items as $item) {
                $student = $item->student()->first();
                if ($student && $coordinator->canAccessStudent($student)) {
                    $item->update([
                        'verification_status' => $validated['verification_status'],
                        'review_notes' => $validated['review_notes'] ?? null,
                        'reviewed_at' => now(),
                    ]);
                }
            }
        });

        return back()->with('success', count($validated['ids']).' '.$validated['type'].'(s) have been '.$validated['verification_status'].'.');
    }

    private function calculatePlacementRate($applications): float
    {
        if ($applications->isEmpty()) {
            return 0;
        }

        $accepted = $applications->where('status', ApplicationStatus::Accepted)->count();
        return round(($accepted / $applications->count()) * 100, 1);
    }
}
