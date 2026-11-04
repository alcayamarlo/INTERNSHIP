<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Services\InstitutionStatistics;
use App\Support\CsvCell;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class StatisticsController extends Controller
{
    public const LABELS = [
        'enrolled' => 'Enrolled students',
        'eligible' => 'Eligible to apply now',
        'approved' => 'Approved by coordinator',
        'rejected' => 'Marked not eligible',
        'placed' => 'Placed',
        'pending_validation' => 'Placements to validate',
        'ongoing' => 'Ongoing OJT',
        'completed' => 'Completed OJT',
        'cleared' => 'Cleared (certificate issued)',
    ];

    public function __construct(private InstitutionStatistics $statistics) {}

    public function index()
    {
        return view('coordinator.statistics.index', ['stats' => $this->stats(), 'labels' => self::LABELS]);
    }

    public function export(string $format)
    {
        $stats = $this->stats();
        $filename = 'ojt-statistics-'.$stats['generated_at']->format('Y-m-d');
        app(\App\Services\ActivityLogService::class)->log(Auth::user(), 'statistics_exported', ['format' => $format]);

        if ($format === 'pdf') {
            return Pdf::loadView('coordinator.statistics.pdf', ['stats' => $stats, 'labels' => self::LABELS])
                ->setPaper('a4')
                ->download($filename.'.pdf');
        }

        // tempnam() creates an empty placeholder; the spreadsheet is written next to it with an .xlsx name.
        $placeholder = tempnam(sys_get_temp_dir(), 'stats');
        $path = $placeholder.'.xlsx';
        @unlink($placeholder);
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['OJT statistics', $stats['generated_at']->format('M d, Y h:i A').' (Philippine time)']));
        $writer->addRow(Row::fromValues([]));
        foreach (self::LABELS as $key => $label) {
            $writer->addRow(Row::fromValues([$label, $stats['totals'][$key]]));
        }
        $writer->addRow(Row::fromValues(['Placement rate (%)', $stats['rates']['placement']]));
        $writer->addRow(Row::fromValues(['Completion rate (%)', $stats['rates']['completion']]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['Program', 'Enrolled', 'Placed', 'Completed']));
        foreach ($stats['programs'] as $row) {
            $writer->addRow(Row::fromValues(CsvCell::row([$row['program'], $row['enrolled'], $row['placed'], $row['completed']])));
        }
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['Company', 'Interns', 'Completed', 'Average evaluation (1-5)', 'Would hire (%)']));
        foreach ($stats['companies'] as $row) {
            // Company names are typed by companies, so a leading "=" must not become a formula.
            $writer->addRow(Row::fromValues(CsvCell::row([$row['company'], $row['interns'], $row['completed'], $row['average_score'] ?? '', $row['would_hire'] ?? ''])));
        }
        $writer->close();

        return response()->download($path, $filename.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function stats(): array
    {
        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator, 403);

        return $this->statistics->for($coordinator);
    }
}
