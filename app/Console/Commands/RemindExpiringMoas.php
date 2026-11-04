<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Employer;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class RemindExpiringMoas extends Command
{
    protected $signature = 'moa:remind {--days=30 : Remind this many days before expiry}';

    protected $description = 'Remind coordinators once about company MOAs that expire soon';

    public function handle(NotificationService $notifications): int
    {
        $companies = Employer::query()
            ->whereNull('moa_reminded_at')
            ->whereDate('moa_expires_on', '>=', now()->toDateString())
            ->whereDate('moa_expires_on', '<=', now()->addDays((int) $this->option('days'))->toDateString())
            ->get();
        $coordinators = User::query()->where('role', UserRole::Coordinator->value)->where('is_active', true)->whereNull('archived_at')->get();

        foreach ($companies as $company) {
            foreach ($coordinators as $coordinator) {
                $notifications->send(
                    $coordinator,
                    'moa_expiring',
                    'MOA expiring soon',
                    "The MOA with {$company->company_name} expires on {$company->moa_expires_on->format('M d, Y')}.",
                    ['employer_id' => $company->id]
                );
            }
            $company->update(['moa_reminded_at' => now()]);
        }

        $this->info("Sent reminders for {$companies->count()} MOA(s).");

        return self::SUCCESS;
    }
}
