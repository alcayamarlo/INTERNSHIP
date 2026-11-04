<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Program;
use App\Models\Student;
use App\Services\InternshipEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Applications and placements of the coordinator's students: counts per stage, a filterable list,
 * and the students who are not placed yet.
 */
class ApplicationMonitorController extends Controller
{
    /** Board stages; "placed" means the accepted application has an OJT placement. */
    public const STAGES = [
        'applied' => 'Applied',
        'reviewed' => 'Reviewed',
        'interview' => 'Interview',
        'accepted' => 'Accepted',
        'placed' => 'Placed',
        'rejected' => 'Declined',
        'withdrawn' => 'Withdrawn',
    ];

    public function __construct(private InternshipEligibility $eligibility) {}

    public function index(Request $request)
    {
        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator, 403);
        $filters = $request->validate([
            'stage' => ['nullable', Rule::in(array_keys(self::STAGES))],
            'program_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $studentIds = $coordinator->scopedStudentsQuery()->select('id');
        $applications = InternshipApplication::query()->whereIn('student_id', $studentIds);

        $counts = [
            'applied' => (clone $applications)->whereIn('status', [ApplicationStatus::Applied->value, ApplicationStatus::Submitted->value])->count(),
            'reviewed' => (clone $applications)->where('status', ApplicationStatus::Reviewed->value)->count(),
            'interview' => (clone $applications)->where('status', ApplicationStatus::Interview->value)->count(),
            'accepted' => (clone $applications)->whereIn('status', [ApplicationStatus::Accepted->value, ApplicationStatus::Completed->value])->doesntHave('placement')->count(),
            'placed' => (clone $applications)->has('placement')->count(),
            'rejected' => (clone $applications)->where('status', ApplicationStatus::Rejected->value)->count(),
            'withdrawn' => (clone $applications)->where('status', ApplicationStatus::Withdrawn->value)->count(),
        ];

        $list = (clone $applications)
            ->with(['student.user', 'internship.employer', 'placement'])
            ->when($filters['stage'] ?? null, fn ($query, string $stage) => match ($stage) {
                'applied' => $query->whereIn('status', [ApplicationStatus::Applied->value, ApplicationStatus::Submitted->value]),
                'accepted' => $query->whereIn('status', [ApplicationStatus::Accepted->value, ApplicationStatus::Completed->value])->doesntHave('placement'),
                'placed' => $query->has('placement'),
                default => $query->where('status', $stage),
            })
            ->when($filters['program_id'] ?? null, fn ($query, int $programId) => $query->whereHas('student', fn ($students) => $students->where('program_id', $programId)))
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query->whereHas('student.user', fn ($users) => $users->where('name', 'like', '%'.$term.'%')))
            ->latest('applied_at')
            ->paginate(15)
            ->withQueryString();

        $unplaced = $coordinator->scopedStudentsQuery()
            ->whereDoesntHave('placements')
            ->with('user')
            ->withCount(['applications as active_applications_count' => fn ($query) => $query->whereIn('status', array_map(
                fn (ApplicationStatus $status) => $status->value,
                array_filter(ApplicationStatus::cases(), fn (ApplicationStatus $status) => $status->isActive())
            ))])
            ->when($filters['program_id'] ?? null, fn ($query, int $programId) => $query->where('program_id', $programId))
            ->orderBy('id')
            ->paginate(15, ['*'], 'unplaced_page')
            ->withQueryString();
        $eligibleIds = $this->eligibility->constrain(Student::query()->whereKey($unplaced->getCollection()->modelKeys()))->pluck('id')->all();

        return view('coordinator.applications.index', [
            'stages' => self::STAGES,
            'counts' => $counts,
            'applications' => $list,
            'unplaced' => $unplaced,
            'eligibleIds' => $eligibleIds,
            'filters' => $filters,
            'programs' => Program::query()->where('coordinator_id', $coordinator->id)->orderBy('code')->get(),
            'pendingValidation' => Placement::query()->whereIn('student_id', $studentIds)->where('status', Placement::STATUS_PENDING_VALIDATION)->count(),
        ]);
    }

    /** Board stage of one application. */
    public static function stageOf(InternshipApplication $application): string
    {
        return match (true) {
            $application->placement !== null => 'placed',
            in_array($application->status, [ApplicationStatus::Applied, ApplicationStatus::Submitted], true) => 'applied',
            $application->status === ApplicationStatus::Completed => 'accepted',
            default => $application->status->value,
        };
    }
}
