<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company size was only kept in the browser session, so it was lost on logout.
 * It now lives on the employer record like the rest of the company profile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->string('company_size', 20)->nullable()->after('industry');
        });
    }

    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn('company_size');
        });
    }
};
