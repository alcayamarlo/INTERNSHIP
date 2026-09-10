<?php

namespace App\Jobs;

use App\Models\Student;
use App\Services\ResumeBuilderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BuildResume implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $studentId
    ) {}

    public function handle(ResumeBuilderService $resumeBuilder): void
    {
        $student = Student::with(['user', 'institution', 'competencies', 'certificates', 'portfolios'])
            ->findOrFail($this->studentId);

        $resumeBuilder->build($student);

        Log::info("Resume built successfully for student {$this->studentId}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to build resume for student {$this->studentId}: {$exception->getMessage()}");
    }
}
