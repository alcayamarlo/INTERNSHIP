<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * alerts_sent_at: when matching students were told about the posting (sent once).
     * deadline_reminded_at: when the "closing soon" reminder went out; cleared when the deadline changes.
     */
    public function up(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->timestamp('alerts_sent_at')->nullable()->after('expires_at');
            $table->timestamp('deadline_reminded_at')->nullable()->after('alerts_sent_at');
        });

        // Postings that already exist were announced before alerts existed; editing them
        // later must not send a "new internship" alert.
        DB::table('internships')->whereNull('alerts_sent_at')->update(['alerts_sent_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->dropColumn(['alerts_sent_at', 'deadline_reminded_at']);
        });
    }
};
