<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A department may have up to 2 coordinators (App\Models\Coordinator::MAX_PER_DEPARTMENT), so the UNIQUE key on
 * coordinators.program_id goes. A plain index replaces it, because MySQL needs an index on the foreign key.
 * The limit of 2 is enforced in code (the admin forms lock the department while they check it). Existing links
 * are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coordinators', function (Blueprint $table) {
            $table->index('program_id', 'coordinators_program_id_index');
        });
        Schema::table('coordinators', function (Blueprint $table) {
            $table->dropUnique(['program_id']);
        });
    }

    public function down(): void
    {
        // Departments with two coordinators would break the unique key, so it is not put back.
        Schema::table('coordinators', function (Blueprint $table) {
            $table->dropIndex('coordinators_program_id_index');
        });
    }
};
