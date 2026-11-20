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
            'backups' => $this->backupFiles(),
        ]);
    }

    /** The encrypted backups kept on this server, newest first. */
    private function backupFiles(): array
    {
        $disk = Storage::disk('local');

        return collect($disk->files('backups'))
            ->filter(fn (string $file) => preg_match('/^backups\/backup_[0-9_-]+\.zip$/', $file))
            ->map(fn (string $file) => ['name' => basename($file), 'size' => $disk->size($file), 'at' => \Illuminate\Support\Carbon::createFromTimestamp($disk->lastModified($file))])
            ->sortByDesc(fn (array $backup) => $backup['at']->getTimestamp())->take(10)->values()->all();
    }

    /** Download one backup kept on this server. Only names this system makes are accepted. */
    public function downloadBackup(string $name)
    {
        $path = $this->backupPath($name);
        $this->activityLog->log(Auth::user(), 'database_backup_downloaded', ['filename' => $name]);

        return response()->download($path, $name);
    }

    /** Opens a backup with BACKUP_PASSWORD and reports what is inside (the same check as `php artisan backup:verify`). */
    public function verifyBackup(string $name, BackupArchive $backups)
    {
        try {
            $result = $backups->inspect($this->backupPath($name));
        } catch (\RuntimeException $exception) {
            return back()->with('error', 'Backup check failed: '.$exception->getMessage());
        }
        $manifest = $result['manifest'];
        $this->activityLog->log(Auth::user(), 'database_backup_verified', ['filename' => $name]);

        return back()->with('success', "{$name} opens with BACKUP_PASSWORD: ".count($manifest['rows'] ?? []).' tables, '.array_sum($manifest['rows'] ?? [])." rows, {$result['files']} uploaded files (".($manifest['rows']['users'] ?? 0).' users, '.($manifest['rows']['students'] ?? 0).' students).');
    }

    private function backupPath(string $name): string
    {
        abort_unless(preg_match('/^backup_[0-9_-]+\.zip$/', $name) && Storage::disk('local')->exists('backups/'.$name), 404);

        return Storage::disk('local')->path('backups/'.$name);
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

            // The messages of BackupArchive and DatabaseBackup are written for admins (the real reason, never a password).
            return back()->with('error', 'Backup failed: '.$exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Backup failed. See the log for details.');
        }

        $this->activityLog->log(Auth::user(), 'database_backup', ['filename' => $result['name'], 'files' => $result['files'], 'encrypted' => true]);

        return response()->download($result['path'], $result['name']);
    }
}
