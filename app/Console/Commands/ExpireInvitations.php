<?php

namespace App\Console\Commands;

use App\Services\InvitationService;
use Illuminate\Console\Command;

class ExpireInvitations extends Command
{
    protected $signature = 'invitations:expire';

    protected $description = 'Expire internship invitations past their date or whose internship closed, and remind students before expiry';

    public function handle(InvitationService $invitations): int
    {
        $result = $invitations->expireAndRemind();
        $this->info("Expired {$result['expired']} invitation(s); sent {$result['reminded']} reminder(s).");

        return self::SUCCESS;
    }
}
