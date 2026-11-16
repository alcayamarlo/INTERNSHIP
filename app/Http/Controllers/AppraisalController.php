<?php

namespace App\Http\Controllers;

use App\Models\Appraisal;
use App\Models\Placement;
use App\Services\AppraisalService;
use App\Services\OjtDocumentData;
use App\Support\OjtFormHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Monthly Evaluation actions for every role. Routes sit in each role's group (role middleware);
 * the policy decides whose form it is and AppraisalService enforces the order of steps.
 */
class AppraisalController extends Controller
{
    public function __construct(private AppraisalService $appraisals, private OjtDocumentData $documents) {}

    /** Coordinator: request one month for one placement. */
    public function store(Request $request, Placement $placement)
    {
        $this->authorize('requestAppraisal', $placement);
        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'due_on' => ['required', 'date', 'after_or_equal:today'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
        ], [
            'due_on.after_or_equal' => 'The due date cannot be in the past.',
        ]);

        $appraisal = $this->appraisals->request(
            $placement,
            Auth::user(),
            CarbonImmutable::createFromFormat('Y-m-d', $validated['period_month'].'-01'),
            CarbonImmutable::parse($validated['due_on']),
            filled($validated['period_start'] ?? null) ? CarbonImmutable::parse($validated['period_start']) : null,
            filled($validated['period_end'] ?? null) ? CarbonImmutable::parse($validated['period_end']) : null,
        );

        return back()->with('success', 'Monthly evaluation for '.$appraisal->period_label.' ('.$appraisal->period_month->format('F Y').') requested. The student rates first.');
    }

    /** Coordinator: request one month for every active placement they manage. */
    public function bulkStore(Request $request)
    {
        abort_unless(Auth::user()->coordinator, 403);
        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'due_on' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'due_on.after_or_equal' => 'The due date cannot be in the past.',
        ]);

        $month = CarbonImmutable::createFromFormat('Y-m-d', $validated['period_month'].'-01');
        $result = $this->appraisals->bulkRequest(Auth::user(), $month, CarbonImmutable::parse($validated['due_on']));

        return back()->with('success', "Monthly evaluation for {$month->format('F Y')} requested for {$result['created']} placement(s). Skipped {$result['skipped']} (already requested, or the month is outside the OJT dates).");
    }

    public function submitSelf(Request $request, Appraisal $appraisal)
    {
        $this->authorize('submitAsStudent', $appraisal);
        // The trainee's details are frozen on the form when the student submits, so they must be complete.
        if ($blocked = $this->documents->blockedResponse($request, $appraisal->placement, 'monthly_evaluation')) {
            return $blocked;
        }
        $validated = $request->validateWithBag('appraisal'.$appraisal->id, AppraisalService::scoreRules(), AppraisalService::scoreMessages());

        return $this->attempt(fn () => $this->appraisals->submitSelf($appraisal, Auth::user(), $validated['scores']),
            'Your rating is submitted. Your supervisor rates next.');
    }

    public function submitSupervisor(Request $request, Appraisal $appraisal)
    {
        $this->authorize('submitAsSupervisor', $appraisal);
        $validated = $request->validateWithBag('appraisal'.$appraisal->id, [
            ...AppraisalService::scoreRules(),
            'supervisor_comment' => ['required', 'string', 'max:3000'],
        ], [
            ...AppraisalService::scoreMessages(),
            'supervisor_comment.required' => 'Write your comment (strengths, areas of improvement, suggestions).',
        ]);

        return $this->attempt(fn () => $this->appraisals->submitSupervisor($appraisal, Auth::user(), $validated['scores'], $validated['supervisor_comment']),
            'Rating certified and submitted. The student adds a comment next.');
    }

    public function submitStudentComment(Request $request, Appraisal $appraisal)
    {
        $this->authorize('submitAsStudent', $appraisal);
        if ($blocked = $this->documents->blockedResponse($request, $appraisal->placement, 'monthly_evaluation', $this->info($appraisal))) {
            return $blocked;
        }
        $validated = $request->validateWithBag('appraisal'.$appraisal->id, [
            'student_comment' => ['required', 'string', 'max:3000'],
        ], [
            'student_comment.required' => 'Write your comment before submitting.',
        ]);

        return $this->attempt(fn () => $this->appraisals->submitStudentComment($appraisal, Auth::user(), $validated['student_comment']),
            'Your comment is submitted. Your coordinator rates last.');
    }

    public function submitAdviser(Request $request, Appraisal $appraisal)
    {
        $this->authorize('submitAsAdviser', $appraisal);
        $validated = $request->validateWithBag('appraisal'.$appraisal->id, AppraisalService::scoreRules(), AppraisalService::scoreMessages());

        return $this->attempt(fn () => $this->appraisals->submitAdviser($appraisal, Auth::user(), $validated['scores']),
            'Adviser rating submitted. The monthly evaluation is complete.');
    }

    public function reopen(Appraisal $appraisal)
    {
        $this->authorize('reopen', $appraisal);

        return $this->attempt(function () use ($appraisal) {
            $step = $this->appraisals->reopen($appraisal, Auth::user());
            session()->flash('success', 'Reopened the '.Appraisal::stepLabel($step).'.');
        }, null);
    }

    /** Printable PDF in the school's Performance Appraisal Report layout. */
    public function print(Request $request, Appraisal $appraisal)
    {
        $this->authorize('view', $appraisal);
        $appraisal->load(['placement.student.user', 'placement.employer.user', 'placement.internship', 'supervisor:id,name', 'adviser:id,name']);

        $info = $this->info($appraisal);
        if ($blocked = $this->documents->blockedResponse($request, $appraisal->placement, 'monthly_evaluation', $info)) {
            return $blocked;
        }

        $pdf = Pdf::loadView('ojt.appraisal-pdf', [
            'appraisal' => $appraisal,
            'placement' => $appraisal->placement,
            'info' => $info,
            'header' => OjtFormHeader::data(),
        ])->setPaper('a4', 'portrait');

        $name = str($info['full_name'] ?: 'student')->slug();

        return $pdf->stream("monthly-evaluation-{$name}-{$appraisal->period_month->format('Y-m')}.pdf");
    }

    /** The trainee details printed on the form: frozen when the student submitted, otherwise the current ones. */
    private function info(Appraisal $appraisal): array
    {
        return OjtDocumentData::withSnapshot($this->documents->values($appraisal->placement), $appraisal->header_snapshot);
    }

    /** Runs a step; a stage problem comes back as a flash error instead of a form error. */
    private function attempt(\Closure $action, ?string $success)
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            if (array_key_exists('appraisal', $exception->errors())) {
                return back()->with('error', $exception->errors()['appraisal'][0]);
            }
            throw $exception;
        }

        $response = back();

        return $success ? $response->with('success', $success) : $response;
    }
}
