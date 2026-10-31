<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Placement;
use App\Services\NotificationService;
use App\Services\PlacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlacementController extends Controller
{
    public function __construct(
        private PlacementService $placementService,
        private NotificationService $notificationService
    ) {}

    public function index(Request $request)
    {
        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator, 403);

        $filter = $request->query('status');
        $search = trim((string) $request->query('q'));

        $placements = Placement::query()
            ->whereIn('student_id', $coordinator->scopedStudentsQuery()->select('id'))
            ->with(['student.user', 'internship', 'employer', 'evaluation:id,placement_id,average_score'])
            ->withApprovedHours()
            ->when($filter === 'ongoing', fn ($query) => $query->where('status', Placement::STATUS_ACTIVE))
            ->when($filter === 'for_clearance', fn ($query) => $query->where('status', Placement::STATUS_COMPLETED)->whereNull('cleared_at'))
            ->when($filter === 'cleared', fn ($query) => $query->whereNotNull('cleared_at'))
            ->when($search !== '', fn ($query) => $query->whereHas('student.user', fn ($users) => $users->where('name', 'like', '%'.$search.'%')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $scoped = Placement::query()->whereIn('student_id', $coordinator->scopedStudentsQuery()->select('id'));
        $summary = [
            'ongoing' => (clone $scoped)->where('status', Placement::STATUS_ACTIVE)->count(),
            'for_clearance' => (clone $scoped)->where('status', Placement::STATUS_COMPLETED)->whereNull('cleared_at')->count(),
            'cleared' => (clone $scoped)->whereNotNull('cleared_at')->count(),
        ];

        return view('coordinator.placements.index', compact('placements', 'summary', 'filter', 'search'));
    }

    public function show(Placement $placement)
    {
        $this->authorize('view', $placement);

        $placement->load([
            'student.user',
            'internship',
            'employer',
            'attendanceLogs' => fn ($query) => $query->latest('work_date'),
            'attendanceLogs.reviewer:id,name',
            'journalEntries' => fn ($query) => $query->latest('week_start'),
            'evaluation.evaluator:id,name',
            'clearedBy:id,name',
        ]);

        return view('coordinator.placements.show', compact('placement'));
    }

    public function clear(Request $request, Placement $placement)
    {
        $this->authorize('clear', $placement);

        $validated = $request->validate([
            'clearance_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->placementService->clear($placement, Auth::user(), $validated['clearance_remarks'] ?? null);

        return back()->with('success', 'OJT cleared. The student can now download the certificate of completion.');
    }

    public function requestRating(Placement $placement)
    {
        $this->authorize('requestRating', $placement);

        $placement->update([
            'rating_requested_at' => now(),
            'rating_requested_by_user_id' => Auth::id(),
        ]);

        $placement->loadMissing('employer.user', 'student.user');
        if ($employerUser = $placement->employer?->user) {
            $this->notificationService->send(
                $employerUser,
                'ojt_update',
                'Rating requested',
                'Your coordinator requested a performance rating for '.($placement->student?->user?->name ?? 'this intern').'.',
                ['placement_id' => $placement->id]
            );
        }

        return back()->with('success', 'Rating request sent to the employer.');
    }
}
