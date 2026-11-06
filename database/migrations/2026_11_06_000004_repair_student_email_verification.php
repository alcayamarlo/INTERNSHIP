<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Makes email_verified_at match what actually happened, for students only.
 *
 * 1. Students who proved their CURRENT email with a code (audit log "email_verified") but have
 *    no timestamp are marked verified, at the time they verified, so they are not asked again.
 * 2. The old student importer stamped email_verified_at on accounts that had no email at all.
 *    That stamp means nothing; left in place, an email typed in later by an admin would count
 *    as verified without a code. It is cleared (these students are asked to add and verify an
 *    email either way, because hasVerifiedEmail() also needs an email).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Restore verification proven in the audit log (latest proof per user, same email only).
        DB::table('system_logs')
            ->where('action', 'email_verified')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->get(['user_id', 'details', 'created_at'])
            ->groupBy('user_id')
            ->each(function ($logs, $userId): void {
                $proof = $logs->last();
                $verifiedEmail = mb_strtolower(trim((string) (json_decode((string) $proof->details, true)['email'] ?? '')));
                if ($verifiedEmail === '') {
                    return;
                }

                DB::table('users')
                    ->where('id', $userId)
                    ->where('role', 'student')
                    ->whereNull('email_verified_at')
                    ->whereRaw('LOWER(email) = ?', [$verifiedEmail])
                    ->update(['email_verified_at' => $proof->created_at ?? now()]);
            });

        // 2. A verification timestamp without an email is meaningless.
        DB::table('users')
            ->where('role', 'student')
            ->whereNull('email')
            ->whereNotNull('email_verified_at')
            ->update(['email_verified_at' => null]);
    }

    public function down(): void
    {
        // Data repair only; the previous timestamps were wrong, so they are not put back.
    }
};
