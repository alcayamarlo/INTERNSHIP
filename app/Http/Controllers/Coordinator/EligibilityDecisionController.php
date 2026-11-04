<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\EligibilityDecision;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Coordinator approve/reject of a student's eligibility. A rejection (reason required) blocks
 * applying even if the rules pass; an approval clears a rejection. Every decision is kept.
 */
class EligibilityDecisionController extends Controller
{
    public function __construct(private ActivityLogService $activityLog, private NotificationService $notifications) {}

    public function store(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('view', $student);
        $validated = $request->validate([
            'decision' => ['required', Rule::in([EligibilityDecision::APPROVED, EligibilityDecision::REJECTED])],
            'reason' => ['nullable', 'string', 'max:1000', 'required_if:decision,'.EligibilityDecision::REJECTED],
        ], [
            'reason.required_if' => 'Give a reason for the rejection. The student will see it.',
        ]);

        $decision = $student->eligibilityDecisions()->create([
            'decision' => $validated['decision'],
            'reason' => filled($validated['reason'] ?? null) ? trim($validated['reason']) : null,
            'decided_by_user_id' => Auth::id(),
        ]);

        $this->activityLog->log(Auth::user(), 'eligibility_'.$decision->decision, [
            'student_id' => $student->id,
            'decision_id' => $decision->id,
        ]);

        $rejected = $decision->isRejected();
        $this->notifications->send(
            $student->user,
            'eligibility_decision',
            $rejected ? 'Internship eligibility: not eligible' : 'Internship eligibility: approved',
            $rejected
                ? 'Your OJT coordinator marked you not eligible to apply for internships yet. Reason: '.$decision->reason
                : 'Your OJT coordinator approved your eligibility. You can apply for internships that are open to your program.'
        );

        return back()->with('success', $rejected ? 'Student marked not eligible. They have been notified.' : 'Student approved. They have been notified.');
    }
}
