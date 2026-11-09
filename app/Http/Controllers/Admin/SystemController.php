<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\GenerateReportRequest;
use App\Models\Report;
use App\Models\SystemLog;
use App\Services\ActivityLogService;
use App\Services\DatabaseBackup;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SystemController extends Controller
{
    public function __construct(
        private ReportExportService $reportService,
        private ActivityLogService $activityLog
    ) {}

    public function logs()
    {
        return view('admin.system.logs', [
            'logs' => SystemLog::with('user')->latest()->paginate(10),
        ]);
    }

    public function reports(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate((new GenerateReportRequest)->rules());

            $report = $this->reportService->generate(
                Auth::user(),
                $validated['type'],
                $validated['format'],
                fn (Report $report) => $this->activityLog->log(Auth::user(), 'report_generate', [
                    'report_id' => $report->id,
                    'type' => $validated['type'],
                ])
            );

            return $this->reportService->download($report);
        }

        return view('admin.system.reports', [
            'reports' => Report::with('generator')->latest()->paginate(10),
        ]);
    }

    public function backup(DatabaseBackup $backups)
    {
        if (empty(config('database.connections.mysql.database'))) {
            return back()->with('error', 'Database backup is unavailable because the MySQL configuration is missing.');
        }

        $filename = 'backups/backup_'.now()->format('Y_m_d_His').'.sql';
        $path = Storage::disk('local')->path($filename);

        try {
            $backups->dumpTo($path);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Database backup failed. Check MYSQLDUMP_PATH in the environment settings and the database connection.');
        }

        $this->activityLog->log(Auth::user(), 'database_backup', ['filename' => $filename]);

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
