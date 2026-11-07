<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipFlag;
use App\Models\Message;
use App\Models\Placement;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Partner company directory for coordinators: contacts, MOA dates, postings (which a coordinator can
 * mark not suitable for their programs), their own students placed there, and the message log.
 */
class CompanyController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function index(Request $request)
    {
        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator, 403);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'moa' => ['nullable', Rule::in(['valid', 'expiring', 'expired', 'none'])],
        ]);
        $studentIds = $coordinator->scopedStudentsQuery()->select('id');
        $today = now()->toDateString();
        $soon = now()->addDays(30)->toDateString();

        $companies = Employer::query()
            ->with('user:id,name,email,phone')
            ->withCount([
                'internships as open_internships_count' => fn ($query) => $query->open(),
                'placements as my_interns_count' => fn ($query) => $query->whereIn('student_id', $studentIds),
            ])
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query->where(fn ($match) => $match
                ->where('company_name', 'like', '%'.$term.'%')
                ->orWhere('industry', 'like', '%'.$term.'%')
                ->orWhere('contact_person', 'like', '%'.$term.'%')))
            ->when($filters['moa'] ?? null, fn ($query, string $moa) => match ($moa) {
                'none' => $query->whereNull('moa_expires_on'),
                'expired' => $query->whereDate('moa_expires_on', '<', $today),
                'expiring' => $query->whereDate('moa_expires_on', '>=', $today)->whereDate('moa_expires_on', '<=', $soon),
                default => $query->whereDate('moa_expires_on', '>', $soon),
            })
            ->orderBy('company_name')
            ->paginate(10)
            ->withQueryString();

        return view('coordinator.companies.index', compact('companies', 'filters'));
    }

    public function show(Employer $employer)
    {
        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator, 403);
        $employer->load('user:id,name,email,phone', 'verifiedBy:id,name');

        $internships = $employer->internships()
            ->open()
            ->with(['programs:id,code', 'flags' => fn ($query) => $query->where('coordinator_id', $coordinator->id)])
            ->orderBy('title')
            ->get();
        $interns = Placement::query()
            ->where('employer_id', $employer->id)
            ->whereIn('student_id', $coordinator->scopedStudentsQuery()->select('id'))
            ->with(['student.user', 'internship:id,title'])
            ->withApprovedHours()
            ->latest()
            ->get();
        // Communication log: messages between this coordinator and the company account.
        $messages = $employer->user
            ? Message::query()
                ->where(fn ($query) => $query->where('sender_id', Auth::id())->where('receiver_id', $employer->user_id))
                ->orWhere(fn ($query) => $query->where('sender_id', $employer->user_id)->where('receiver_id', Auth::id()))
                ->latest()
                ->take(10)
                ->get()
            : collect();

        return view('coordinator.companies.show', compact('employer', 'internships', 'interns', 'messages'));
    }

    public function updateMoa(Request $request, Employer $employer)
    {
        abort_unless(Auth::user()->coordinator, 403);
        $validated = $request->validate([
            'moa_signed_on' => ['nullable', 'date', 'before_or_equal:today'],
            'moa_expires_on' => ['nullable', 'date', 'after_or_equal:moa_signed_on'],
        ], [
            'moa_expires_on.after_or_equal' => 'The expiry date must be on or after the signing date.',
        ]);

        $employer->update([
            'moa_signed_on' => $validated['moa_signed_on'] ?? null,
            'moa_expires_on' => $validated['moa_expires_on'] ?? null,
            // A new expiry date gets its own reminder.
            'moa_reminded_at' => null,
            'moa_updated_by_user_id' => Auth::id(),
        ]);
        $this->activityLog->log(Auth::user(), 'company_moa_updated', ['employer_id' => $employer->id]);

        return back()->with('success', 'MOA dates saved.');
    }

    /** Marks a posting not suitable for this coordinator's programs; their students stop seeing it. */
    public function flag(Request $request, Internship $internship)
    {
        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator, 403);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']], [
            'reason.required' => 'Say why this posting is not suitable.',
        ]);

        InternshipFlag::updateOrCreate(
            ['internship_id' => $internship->id, 'coordinator_id' => $coordinator->id],
            ['reason' => $validated['reason']]
        );
        $this->activityLog->log(Auth::user(), 'internship_flagged', ['internship_id' => $internship->id]);

        return back()->with('success', 'Posting marked not suitable. Your students will no longer see it.');
    }

    public function unflag(Internship $internship)
    {
        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator, 403);
        InternshipFlag::query()->where('internship_id', $internship->id)->where('coordinator_id', $coordinator->id)->delete();
        $this->activityLog->log(Auth::user(), 'internship_unflagged', ['internship_id' => $internship->id]);

        return back()->with('success', 'Posting is visible to your students again.');
    }
}
