<?php

namespace App\Console\Commands;

use App\Services\CompanyInvitationService;
use Illuminate\Console\Command;

class ExpireCompanyInvitations extends Command
{
    protected $signature = 'company-invitations:expire';

    protected $description = 'Mark company invitations past their expiry date as expired';

    public function handle(CompanyInvitationService $invitations): int
    {
        $count = $invitations->expireStale();
        $this->info("Expired {$count} company invitation(s).");

        return self::SUCCESS;
    }
}
