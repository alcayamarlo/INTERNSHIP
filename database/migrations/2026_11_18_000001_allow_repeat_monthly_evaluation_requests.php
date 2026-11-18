<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A coordinator may request the same month again for the same placement (no limit), so the one-per-month
 * unique key goes. A plain index on the same columns replaces it, because MySQL needs an index on
 * placement_id for the foreign key and the lookups by placement and month still use it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appraisals', function (Blueprint $table) {
            $table->index(['placement_id', 'period_month'], 'appraisals_placement_month_index');
        });
        Schema::table('appraisals', function (Blueprint $table) {
            $table->dropUnique(['placement_id', 'period_month']);
        });
    }

    public function down(): void
    {
        // Repeat requests may exist by now, so the unique key is not put back.
        Schema::table('appraisals', function (Blueprint $table) {
            $table->dropIndex('appraisals_placement_month_index');
            $table->index('placement_id');
        });
    }
};
