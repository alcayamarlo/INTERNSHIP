<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\GenerateReportRequest;
use App\Models\Report;
use App\Services\ActivityLogService;
use App\Services\ReportExportService;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(
        private ReportExportService $reportService,
        private ActivityLogService $activityLog
    ) {}

    public function index()
    {
        return view('coordinator.reports.index', [
            'reports' => Report::where('generated_by', Auth::id())->latest()->paginate(10),
        ]);
    }

    public function generate(GenerateReportRequest $request)
    {
        $validated = $request->validated();

        $report = $this->reportService->generate(
            Auth::user(),
            $validated['type'],
            $validated['format']
        );

        $this->activityLog->log(Auth::user(), 'report_generate', [
            'report_id' => $report->id,
            'type' => $validated['type'],
        ]);

        return $this->reportService->download($report);
    }
}
