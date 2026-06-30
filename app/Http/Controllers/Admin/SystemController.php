<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\GenerateReportRequest;
use App\Models\Report;
use App\Models\SystemLog;
use App\Services\ActivityLogService;
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
            'logs' => SystemLog::with('user')->latest()->paginate(20),
        ]);
    }

    public function reports(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate((new GenerateReportRequest)->rules());

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

        return view('admin.system.reports', [
            'reports' => Report::with('generator')->latest()->paginate(10),
        ]);
    }

    public function backup()
    {
        $database = config('database.connections.mysql.database');
        $filename = 'backups/backup_'.now()->format('Y_m_d_His').'.sql';
        $path = storage_path('app/private/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg(config('database.connections.mysql.username')),
            escapeshellarg(config('database.connections.mysql.password')),
            escapeshellarg(config('database.connections.mysql.host')),
            escapeshellarg($database),
            escapeshellarg($path)
        );

        exec($command, $output, $result);

        if ($result !== 0 || ! file_exists($path)) {
            return back()->with('error', 'Database backup failed. Ensure mysqldump is available in your PATH.');
        }

        $this->activityLog->log(Auth::user(), 'database_backup', ['filename' => $filename]);

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
