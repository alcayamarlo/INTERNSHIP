<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    /** Audit and security entries are kept this long, then pruned by logs:prune. */
    public const RETENTION_DAYS = 365;

    /** Sign-in and account-security events, shown on the Security tab. */
    public const SECURITY_ACTIONS = [
        'login',
        'logout',
        'login_failed',
        'login_blocked',
        'login_throttled',
        'password_reset_requested',
        'password_reset',
        'password_reset_failed',
        'password_force_change',
        'admin_password_set',
        'role_changed',
        'account_activated',
        'account_deactivated',
        'account_suspended',
        'account_suspension_lifted',
    ];

    /** Failed sign-ins from one account or IP within this window count as repeated. */
    public const REPEATED_FAILURE_HOURS = 24;

    public const REPEATED_FAILURE_COUNT = 5;

    public function isSecurityEvent(): bool
    {
        return in_array($this->action, self::SECURITY_ACTIONS, true);
    }

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
