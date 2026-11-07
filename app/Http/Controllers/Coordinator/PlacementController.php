<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Placement;
use App\Services\NotificationService;
use App\Services\PlacementService;
use App\Services\PlacementValidation;
use App\Services\ActivityLogService;
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
            ->when($filter === 'pending_validation', fn ($query) => $query->where('status', Placement::STATUS_PENDING_VALIDATION))
            ->when($filter === 'at_risk', fn ($query) => $query->atRisk())
            ->when($filter === 'ongoing', fn ($query) => $query->where('status', Placement::STATUS_ACTIVE))
            ->when($filter === 'for_clearance', fn ($query) => $query->where('status', Placement::STATUS_COMPLETED)->whereNull('cleared_at'))
            ->when($filter === 'cleared', fn ($query) => $query->whereNotNull('cleared_at'))
            ->when($search !== '', fn ($query) => $query->whereHas('student.user', fn ($users) => $users->where('name', 'like', '%'.$search.'%')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $scoped = Placement::query()->whereIn('student_id', $coordinator->scopedStudentsQuery()->select('id'));
        $summary = [
            'pending_validation' => (clone $scoped)->where('status', Placement::STATUS_PENDING_VALIDATION)->count(),
            'at_risk' => (clone $scoped)->atRisk()->count(),
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

        $validationChecks = $placement->isPendingValidation() ? app(PlacementValidation::class)->checks($placement) : [];

        return view('coordinator.placements.show', compact('placement', 'validationChecks'));
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

    /** Deploys the student: the placement becomes active and hours can be logged. */
    public function validatePlacement(Request $request, Placement $placement, PlacementValidation $validation, ActivityLogService $activityLog)
    {
        $this->authorize('view', $placement);
        abort_unless($placement->isPendingValidation(), 409, 'This placement is not waiting for validation.');

        $checks = $validation->checks($placement);
        if ($validation->hasBlockingFailure($checks)) {
            return back()->with('error', 'This company is not verified yet, so the placement cannot be validated.');
        }
        if ($validation->hasWarnings($checks) && ! $request->boolean('acknowledge_warnings')) {
            return back()->with('error', 'Some checks did not pass. Tick "I reviewed the warnings" to validate anyway.');
        }

        $placement->update([
            'status' => Placement::STATUS_ACTIVE,
            'start_date' => now()->toDateString(),
            'validated_at' => now(),
            'validated_by_user_id' => Auth::id(),
            'validation_remarks' => null,
        ]);
        $activityLog->log(Auth::user(), 'placement_validated', ['placement_id' => $placement->id]);
        $this->notifyParties($placement, 'Placement validated', 'The OJT placement for '.($placement->internship?->title ?? 'the internship').' was validated by the coordinator. Time logging is now open.');

        return back()->with('success', 'Placement validated. The student can now log hours.');
    }

    /** Keeps the placement pending with remarks for the company and student. */
    public function hold(Request $request, Placement $placement, ActivityLogService $activityLog)
    {
        $this->authorize('view', $placement);
        abort_unless($placement->isPendingValidation(), 409, 'This placement is not waiting for validation.');
        $validated = $request->validate(['validation_remarks' => ['required', 'string', 'max:1000']], [
            'validation_remarks.required' => 'Explain what is needed before you can validate this placement.',
        ]);

        $placement->update(['validation_remarks' => $validated['validation_remarks']]);
        $activityLog->log(Auth::user(), 'placement_held', ['placement_id' => $placement->id]);
        $this->notifyParties($placement, 'Placement on hold', 'Your coordinator needs something before validating the OJT placement: '.$validated['validation_remarks']);

        return back()->with('success', 'Remarks sent. The placement stays pending.');
    }

    private function notifyParties(Placement $placement, string $title, string $message): void
    {
        $placement->loadMissing('employer.user', 'student.user', 'internship');
        foreach (array_filter([$placement->student?->user, $placement->employer?->user]) as $user) {
            $this->notificationService->send($user, 'ojt_update', $title, $message, ['placement_id' => $placement->id]);
        }
    }
}
