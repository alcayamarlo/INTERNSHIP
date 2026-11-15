<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\Placement;
use App\Services\OjtDocumentData;
use App\Services\WeeklyReportService;
use App\Support\OjtFormHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Printable school forms: the weekly Activity Narrative Report and the Final Performance Evaluation
 * Report. Access follows PlacementPolicy::view (student own, supervisor own company, managing
 * coordinator, admin). A form whose trainee details are incomplete is not produced.
 */
class OjtFormPrintController extends Controller
{
    public function journal(Request $request, JournalEntry $journalEntry, WeeklyReportService $weeklyReports, OjtDocumentData $documents)
    {
        $journalEntry->load(['placement.student.user', 'placement.employer.user', 'reviewer:id,name']);
        $this->authorize('view', $journalEntry->placement);

        $data = $weeklyReports->printData($journalEntry);
        if ($blocked = $documents->blockedResponse($request, $journalEntry->placement, 'weekly_report', $data['header'])) {
            return $blocked;
        }

        $pdf = Pdf::loadView('ojt.journal-pdf', $data)->setPaper('letter', 'portrait');
        $name = str($data['header']['full_name'] ?: 'student')->slug();

        return $pdf->stream("weekly-report-{$name}-week-{$journalEntry->week_number}.pdf");
    }

    public function evaluation(Request $request, Placement $placement, OjtDocumentData $documents)
    {
        $this->authorize('view', $placement);
        $placement->load(['student.user', 'employer.user', 'internship', 'evaluation.evaluator:id,name']);
        $evaluation = $placement->evaluation;
        // Only the school form has a printable layout; the old 1-5 rubric is shown on screen as before.
        abort_if(! $evaluation || $evaluation->isLegacy(), 404);

        $info = OjtDocumentData::withSnapshot($documents->values($placement), $evaluation->header_snapshot);
        if ($blocked = $documents->blockedResponse($request, $placement, 'final_evaluation', $info)) {
            return $blocked;
        }

        $pdf = Pdf::loadView('ojt.evaluation-pdf', [
            'placement' => $placement,
            'evaluation' => $evaluation,
            'info' => $info,
            'approvedHours' => $placement->approvedHours(),
            'header' => OjtFormHeader::data(),
        ])->setPaper('a4', 'portrait');

        $name = str($info['full_name'] ?: 'student')->slug();

        return $pdf->stream("final-evaluation-{$name}.pdf");
    }
}
