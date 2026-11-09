<?php

namespace App\Console\Commands;

use App\Models\Internship;
use App\Services\InternshipAlertService;
use Illuminate\Console\Command;

class RemindInternshipDeadlines extends Command
{
    protected $signature = 'internships:remind-deadlines {--days= : Remind this many days before the deadline (default from config/internships.php)}';

    protected $description = 'Remind matching students and the company once when an internship posting closes soon';

    public function handle(InternshipAlertService $alerts): int
    {
        $days = (int) ($this->option('days') ?? config('internships.deadline_reminder_days', 3));

        $internships = Internship::query()
            ->open()
            ->whereNull('deadline_reminded_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days)->endOfDay())
            ->get();

        $students = 0;
        $failed = 0;
        foreach ($internships as $internship) {
            try {
                $students += $alerts->sendDeadlineReminder($internship)['students'];
            } catch (\Throwable $exception) {
                // One bad posting must not stop the rest; it stays unmarked for the next run.
                report($exception);
                $failed++;
            }
        }

        $this->info("Sent deadline reminders for {$internships->count()} posting(s) to {$students} student(s), {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
