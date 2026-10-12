<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\InternshipApplication;
use App\Models\AcademicSetting;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Models\VerificationActivity;
use App\Support\StudentIdFormat;
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

        $stats = Cache::remember("coordinator_stats_{$coordinator->id}_{$institutionId}", 300, function () use ($coordinator, $institutionId) {
            $studentIds = $coordinator->scopedStudentsQuery()->pluck('id');

            $applications = InternshipApplication::whereIn('student_id', $studentIds)->get();

            return [
                'total_students' => $studentIds->count(),
                'total_applications' => $applications->count(),
                'accepted' => $applications->where('status', ApplicationStatus::Accepted)->count(),
                'placement_rate' => $this->calculatePlacementRate($applications),
            ];
        });

        $recentStudents = Cache::remember("coordinator_recent_students_{$coordinator->id}_{$institutionId}", 300, function () use ($coordinator) {
            return $coordinator->scopedStudentsQuery()->with('user')->latest()->take(5)->get();
        });

        $recentApplications = Cache::remember("coordinator_recent_apps_{$coordinator->id}_{$institutionId}", 300, function () use ($coordinator) {
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
     * List students assigned to the coordinator, plus unassigned students at their institution.
     */
    public function students(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'program' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'in:name,completion_desc,completion_asc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator && $coordinator->institution_id, 403, 'No institution has been assigned to this coordinator.');

        $search = trim($validated['search'] ?? '');
        $sort = $validated['sort'] ?? 'name';
        $scope = $coordinator->scopedStudentsQuery();

        $summary = [
            'total' => (clone $scope)->count(),
            'average_completion' => round((float) ((clone $scope)->avg('profile_completion') ?? 0)),
            'below_50' => (clone $scope)->where('profile_completion', '<', 50)->count(),
        ];

        $programs = (clone $scope)
            ->whereNotNull('program')
            ->where('program', '<>', '')
            ->distinct()
            ->orderBy('program')
            ->pluck('program');

        $query = $coordinator->scopedStudentsQuery()
            ->with('user:id,name,email')
            ->withCount([
                'competencies',
                'certificates',
                'portfolios',
                'competencies as verified_competencies_count' => fn ($query) => $query->where('verification_status', 'verified'),
                'certificates as verified_certificates_count' => fn ($query) => $query->where('verification_status', 'verified'),
                'portfolios as verified_portfolios_count' => fn ($query) => $query->where('verification_status', 'verified'),
            ])
            ->when(isset($validated['program']) && $validated['program'] !== '', fn ($query) => $query->where('program', $validated['program']))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($studentQuery) use ($search) {
                    $studentQuery->where('student_id_number', 'like', "%{$search}%")
                        ->orWhere('program', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('competencies', fn ($competencyQuery) => $competencyQuery
                            ->where('name', 'like', "%{$search}%"));
                });
            });

        match ($sort) {
            'completion_desc' => $query->orderByDesc('profile_completion')->orderBy('id'),
            'completion_asc' => $query->orderBy('profile_completion')->orderBy('id'),
            default => $query->orderBy(User::select('name')->whereColumn('users.id', 'students.user_id')),
        };

        $students = $query->paginate(15)->withQueryString();

        $studentIdPattern = StudentIdFormat::pattern();
        $payload = $students->getCollection()->map(
            fn (Student $student) => $this->studentListPayload($student, $studentIdPattern)
        );

        if ($request->expectsJson()) {
            return response()->json([
                'students' => $payload,
                'pagination' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                ],
                'summary' => $summary,
            ]);
        }

        return view('coordinator.students.index', [
            'initialStudents' => $payload,
            'pagination' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
            'summary' => $summary,
            'programs' => $programs,
            'filters' => [
                'search' => $search,
                'program' => $validated['program'] ?? '',
                'sort' => $sort,
            ],
        ]);
    }

    private function studentListPayload(Student $student, string $studentIdPattern): array
    {
        $totalSubmissions = $student->competencies_count
            + $student->certificates_count
            + $student->portfolios_count;
        $verifiedSubmissions = $student->verified_competencies_count
            + $student->verified_certificates_count
            + $student->verified_portfolios_count;

        return [
            'id' => $student->id,
            'name' => $student->user->name,
            'email' => $student->user->email,
            'student_id_number' => $student->student_id_number,
            'student_id_needs_correction' => $student->studentIdNeedsCorrection($studentIdPattern),
            'program' => $student->program,
            'year_level' => $student->year_level,
            'profile_completion' => (int) ($student->profile_completion ?? 0),
            'competencies_count' => $student->competencies_count,
            'verified_submissions_count' => $verifiedSubmissions,
            'total_submissions_count' => $totalSubmissions,
            'view_url' => route('coordinator.students.show', $student),
        ];
    }

    /**
     * Show detailed information for a specific student.
     */
    public function showStudent(Student $student)
    {
        $this->authorize('view', $student);
        $studentIdNeedsCorrection = $student->studentIdNeedsCorrection();

        $student->load([
            'user',
            'institution',
            'academicProgram',
            'competencies',
            'certificates',
            'portfolios',
        ]);
        $requiredInternshipHours = $student->academicProgram?->required_internship_hours;
        $renderedInternshipHours = (int) $student->rendered_internship_hours;
        $internshipHoursProgress = $requiredInternshipHours
            ? min(100, (int) round(($renderedInternshipHours / $requiredInternshipHours) * 100))
            : 0;

        $competencies = $student->competencies->map(fn ($item) => [
            'name' => $item->name,
            'category' => $item->category?->label() ?? 'Competency',
            'level' => $item->proficiency_level?->value ?? 'beginner',
            'level_label' => $item->proficiency_level?->label() ?? 'Beginner',
            'status' => $this->verificationStatus($item->verification_status),
        ]);
        $certificates = $student->certificates->map(fn ($item) => [
            'title' => $item->title,
            'issuer' => $item->issuer,
            'status' => $this->verificationStatus($item->verification_status),
        ]);
        $portfolios = $student->portfolios->map(fn ($item) => [
            'title' => $item->title,
            'description' => $item->description,
            'status' => $this->verificationStatus($item->verification_status),
        ]);

        $submissions = $competencies->concat($certificates)->concat($portfolios);
        $verificationCounts = [
            'verified' => $submissions->where('status', 'verified')->count(),
            'pending' => $submissions->where('status', 'pending')->count(),
            'rejected' => $submissions->where('status', 'rejected')->count(),
            'total' => $submissions->count(),
        ];

        $recentActivity = VerificationActivity::query()
            ->where('student_id', $student->id)
            ->with('coordinator.user:id,name')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (VerificationActivity $activity) => [
                'item' => $activity->item,
                'action' => $activity->action,
                'note' => $activity->note,
                'actor' => $activity->coordinator?->user?->name ?? 'Coordinator',
                'created_at' => $activity->created_at?->diffForHumans(),
                'is_verification' => in_array($activity->action, ['verified', 'rejected'], true),
            ]);
        $changeRequests = $student->changeRequests()->latest()->get()->map(function ($changeRequest) {
            $changeRequest->field_label = match ($changeRequest->field) {
                'student_id_number' => 'Student ID',
                'program_id' => 'Program',
                'year_level' => 'Year level',
                default => ucfirst(str_replace('_', ' ', $changeRequest->field)),
            };
            $changeRequest->requested_value_label = $changeRequest->field === 'program_id'
                ? Program::find($changeRequest->requested_value)?->display_name ?? 'Program no longer available'
                : $changeRequest->requested_value;
            $changeRequest->current_value_label = $changeRequest->field === 'program_id'
                ? Program::find($changeRequest->current_value)?->display_name ?? 'Program not set'
                : ($changeRequest->current_value ?: 'Not set');

            return $changeRequest;
        });
        $profileCompletion = max(0, min(100, (int) ($student->profile_completion ?? 0)));
        $initials = collect(preg_split('/\s+/', trim($student->user->name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_substr($part, 0, 1))
            ->implode('');

        return view('coordinator.students.show', [
            'student' => $student,
            'studentIdNeedsCorrection' => $studentIdNeedsCorrection,
            'changeRequests' => $changeRequests,
            'requiredInternshipHours' => $requiredInternshipHours,
            'renderedInternshipHours' => $renderedInternshipHours,
            'internshipHoursProgress' => $internshipHoursProgress,
            'programs' => Program::orderBy('code')->get(),
            'competencies' => $competencies,
            'certificates' => $certificates,
            'portfolios' => $portfolios,
            'verificationCounts' => $verificationCounts,
            'recentActivity' => $recentActivity,
            'profileCompletion' => $profileCompletion,
            'initials' => $initials,
        ]);
    }

    private function verificationStatus(?string $status): string
    {
        return match ($status) {
            'verified', 'rejected' => $status,
            default => 'pending',
        };
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
