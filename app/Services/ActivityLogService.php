<?php

namespace App\Services;

use App\Models\Internship;
use App\Models\Student;
use App\Models\SystemLog;
use App\Models\User;
use App\Support\IpHash;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public function log(?User $user, string $action, array $details = []): void
    {
        SystemLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'ip_address' => IpHash::make(Request::ip()), // a keyed hash, never the address
            'user_agent' => Request::userAgent(),
            'details' => $details,
        ]);
    }
}
