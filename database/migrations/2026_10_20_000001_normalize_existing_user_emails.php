<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Addresses shared by several accounts once normalized are left
        // untouched (and logged) so the deploy does not fail; an admin must
        // merge or archive the duplicates manually.
        $duplicates = DB::table('users')
            ->selectRaw('LOWER(TRIM(email)) as normalized_email')
            ->groupBy('normalized_email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_email')
            ->all();

        if ($duplicates !== []) {
            Log::warning('Skipped normalizing user emails shared by multiple accounts.', [
                'emails' => $duplicates,
            ]);
        }

        DB::table('users')->orderBy('id')->chunkById(500, function ($users) use ($duplicates): void {
            foreach ($users as $user) {
                $email = mb_strtolower(trim($user->email));
                if ($email !== $user->email && ! in_array($email, $duplicates, true)) {
                    DB::table('users')->where('id', $user->id)->update(['email' => $email]);
                }
            }
        });
    }

    public function down(): void
    {
        // Email normalization is intentionally permanent.
    }
};
