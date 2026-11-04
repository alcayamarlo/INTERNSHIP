<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Suspends or reinstates an account; used from the Users page and from moderation. */
class AccountSuspension
{
    public function __construct(private ActivityLogService $activityLog) {}

    /**
     * @throws ValidationException when the account cannot be suspended
     */
    public function suspend(User $user, User $actor, string $reason, ?Carbon $until, array $context = []): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages(['suspension_reason' => 'You cannot suspend your own account.']);
        }
        if ($user->isRole(UserRole::Admin)
            && User::query()->where('role', UserRole::Admin->value)->where('is_active', true)->whereNull('suspended_at')->count() <= 1) {
            throw ValidationException::withMessages(['suspension_reason' => 'The last active administrator cannot be suspended.']);
        }

        $user->forceFill([
            'suspended_at' => now(),
            'suspended_until' => $until,
            'suspension_reason' => $reason,
            'suspended_by_user_id' => $actor->id,
        ])->save();

        // End their open sessions so the suspension applies at once.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        $this->activityLog->log($actor, 'account_suspended', [
            'user_id' => $user->id,
            'until' => $until?->toIso8601String(),
            'reason' => $reason,
            ...$context,
        ]);
    }

    public function lift(User $user, User $actor): void
    {
        $user->forceFill(['suspended_at' => null, 'suspended_until' => null, 'suspension_reason' => null, 'suspended_by_user_id' => null])->save();
        $this->activityLog->log($actor, 'account_suspension_lifted', ['user_id' => $user->id]);
    }
}
