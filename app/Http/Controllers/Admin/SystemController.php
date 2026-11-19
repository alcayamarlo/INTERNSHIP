<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\GenerateReportRequest;
use App\Models\Report;
use App\Models\SystemLog;
use App\Services\ActivityLogService;
use App\Services\BackupArchive;
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

    /** An encrypted backup of the whole database and every uploaded file, downloaded and kept in storage/app/backups. */
    public function backup(BackupArchive $backups)
    {
        if (empty(config('database.connections.mysql.database'))) {
            return back()->with('error', 'Backup is unavailable because the MySQL configuration is missing.');
        }

        try {
            $result = $backups->create();
        } catch (\RuntimeException $exception) {
            report($exception);

            // The messages of BackupArchive are written for admins (a missing password, a failed check).
            return back()->with('error', str_starts_with($exception->getMessage(), 'mysqldump')
                ? 'Backup failed. Check MYSQLDUMP_PATH in the environment settings and the database connection.'
                : $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Backup failed. See the log for details.');
        }

        $this->activityLog->log(Auth::user(), 'database_backup', ['filename' => $result['name'], 'files' => $result['files'], 'encrypted' => true]);

        return response()->download($result['path'], $result['name']);
    }
}
