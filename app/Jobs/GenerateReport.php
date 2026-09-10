<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public int $userId,
        public string $type,
        public string $format = 'pdf'
    ) {}

    public function handle(ReportExportService $reportService): void
    {
        $user = User::findOrFail($this->userId);

        $report = $reportService->generate($user, $this->type, $this->format);

        Log::info("Report generated successfully", [
            'user_id' => $this->userId,
            'type' => $this->type,
            'format' => $this->format,
            'report_id' => $report->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to generate report", [
            'user_id' => $this->userId,
            'type' => $this->type,
            'error' => $exception->getMessage(),
        ]);
    }
}
