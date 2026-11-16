<?php

namespace App\Http\Controllers\Employer;

use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invitation\StoreInvitationRequest;
use App\Models\Internship;
use App\Models\InternshipInvitation;
use App\Models\Student;
use App\Services\CompanyVisibleProfile;
use App\Services\InvitationService;
use App\Support\StudentListFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitations) {}

    public function index(Request $request)
    {
        $employer = Auth::user()->employer;
        $filters = new StudentListFilters($request, [
            'pending' => ['label' => 'Pending', 'statuses' => [InvitationStatus::Pending->value]],
            'accepted' => ['label' => 'Accepted', 'statuses' => [InvitationStatus::Accepted->value]],
            'declined' => ['label' => 'Declined', 'statuses' => [InvitationStatus::Declined->value]],
            'expired' => ['label' => 'Expired', 'statuses' => [InvitationStatus::Expired->value]],
        ]);
        $base = InternshipInvitation::query()->where('employer_id', $employer->id);
        $invitations = $filters->apply(clone $base)
            ->with(['student.user', 'internship:id,title,status,expires_at'])
            ->latest()
            ->paginate(10)
            ->withQueryString();
        $visibleProfile = app(CompanyVisibleProfile::class);

        return view('employer.invitations.index', [
            'invitations' => $invitations,
            'filters' => $filters,
            'filterOptions' => $filters->options($base),
            'totalCount' => (clone $base)->count(),
            // Name, photo, and program only.
            'studentSummaries' => $invitations->getCollection()
                ->mapWithKeys(fn (InternshipInvitation $invitation) => [$invitation->id => $visibleProfile->summary($invitation->student)]),
            'sentToday' => $this->invitations->sentToday($employer),
            'dailyLimit' => (int) config('invitations.daily_limit', 20),
        ]);
    }

    public function store(StoreInvitationRequest $request)
    {
        $internship = Internship::findOrFail($request->integer('internship_id'));
        $this->authorize('create', [InternshipInvitation::class, $internship]);

        $student = Student::with('user')->findOrFail($request->integer('student_id'));
        $this->invitations->invite($request->user(), $student, $internship, $request->validated('message'));

        $message = "Invitation sent to {$student->user->name} for {$internship->title}.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message], 201);
        }

        return back()->with('success', $message);
    }
}
