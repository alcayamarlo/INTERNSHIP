<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\Placement;
use App\Services\WeeklyReportService;
use App\Support\OjtFormHeader;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Printable school forms: the weekly Activity Narrative Report and the Final Performance Evaluation
 * Report. Access follows PlacementPolicy::view (student own, supervisor own company, managing
 * coordinator, admin).
 */
class OjtFormPrintController extends Controller
{
    public function journal(JournalEntry $journalEntry, WeeklyReportService $weeklyReports)
    {
        $journalEntry->load(['placement.student.user', 'placement.employer', 'reviewer:id,name']);
        $this->authorize('view', $journalEntry->placement);

        $pdf = Pdf::loadView('ojt.journal-pdf', [
            'entry' => $journalEntry,
            'placement' => $journalEntry->placement,
            'rows' => $weeklyReports->dailyRows($journalEntry),
            'periodLabel' => $weeklyReports->periodLabel($journalEntry),
            'header' => OjtFormHeader::data(),
        ])->setPaper('a4', 'portrait');

        $name = str($journalEntry->placement?->student?->user?->name ?? 'student')->slug();

        return $pdf->stream("weekly-report-{$name}-week-{$journalEntry->week_number}.pdf");
    }

    public function evaluation(Placement $placement)
    {
        $this->authorize('view', $placement);
        $placement->load(['student.user', 'employer', 'internship', 'evaluation.evaluator:id,name']);
        $evaluation = $placement->evaluation;
        // Only the school form has a printable layout; the old 1-5 rubric is shown on screen as before.
        abort_if(! $evaluation || $evaluation->isLegacy(), 404);

        $pdf = Pdf::loadView('ojt.evaluation-pdf', [
            'placement' => $placement,
            'evaluation' => $evaluation,
            'approvedHours' => $placement->approvedHours(),
            'header' => OjtFormHeader::data(),
        ])->setPaper('a4', 'portrait');

        $name = str($placement->student?->user?->name ?? 'student')->slug();

        return $pdf->stream("final-evaluation-{$name}.pdf");
    }
}
