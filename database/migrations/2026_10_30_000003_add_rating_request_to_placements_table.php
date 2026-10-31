<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->timestamp('rating_requested_at')->nullable()->after('cleared_at');
            $table->foreignId('rating_requested_by_user_id')->nullable()->after('rating_requested_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rating_requested_by_user_id');
            $table->dropColumn('rating_requested_at');
        });
    }
};
