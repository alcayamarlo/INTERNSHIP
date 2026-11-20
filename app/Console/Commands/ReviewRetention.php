<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\RetentionReview;
use Illuminate\Console\Command;

class ReviewRetention extends Command
{
    protected $signature = 'retention:review {--notify : Also notify the administrators when there is something to review}';

    protected $description = 'List student accounts past the retention period (default 3 years). Never deletes anything.';

    public function handle(RetentionReview $review, NotificationService $notifications, ActivityLogService $log): int
    {
        $years = (int) config('privacy.retention_years');
        $candidates = $review->candidates();
        $this->info("Retention period: {$years} years (unused since before {$review->cutoff()->toDateString()}).");
        $this->line($candidates->count().' student account'.($candidates->count() === 1 ? '' : 's').' past it.');
        $this->table(['School ID', 'Name', 'Last activity', 'Basis'], $candidates->take(20)->map(fn (object $row) => [
            $row->student->student_id_number ?? '-', $row->student->user->name, $row->last_activity->toDateString(), $row->basis,
        ])->all());
        if ($candidates->count() > 20) {
            $this->line('... and '.($candidates->count() - 20).' more (see Admin > Retention Review).');
        }

        $log->log(null, 'retention_review', ['accounts' => $candidates->count(), 'years' => $years]);
        if ($this->option('notify') && $candidates->isNotEmpty()) {
            foreach (User::query()->where('role', UserRole::Admin->value)->where('is_active', true)->get() as $admin) {
                $notifications->send($admin, 'retention_review', 'Accounts to review for deletion', $candidates->count().' student account'.($candidates->count() === 1 ? ' is' : 's are')." past the {$years}-year retention period. Nothing is deleted until you approve it.", ['accounts' => $candidates->count()]);
            }
        }

        return self::SUCCESS;
    }
}
