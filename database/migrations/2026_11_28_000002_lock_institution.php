<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SkillBridge serves one school (App\Models\Institution::primary()). Makes sure it exists, and points every record that
 * carries an institution_id (students, coordinators, ...) at it, including records with none. Other institution rows, if
 * any, are left in place (nothing is deleted); they simply have no one pointing at them.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A fresh install has nothing to point yet: Institution::primary() creates the school the first time it is needed.
        if (DB::table('institutions')->doesntExist() && DB::table('students')->doesntExist() && DB::table('coordinators')->doesntExist()) {
            return;
        }

        $name = "St. Cecilia's College-Cebu, Inc.";
        $existing = DB::table('institutions')->where('name', $name)->first();
        $id = $existing?->id ?? DB::table('institutions')->insertGetId(['name' => $name, 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        if ($existing && $existing->verified_at === null) {
            DB::table('institutions')->where('id', $id)->update(['verified_at' => now()]);
        }

        foreach (['students', 'coordinators'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'institution_id')) {
                DB::table($table)->where(fn ($q) => $q->whereNull('institution_id')->orWhere('institution_id', '<>', $id))->update(['institution_id' => $id]);
            }
        }
    }

    public function down(): void
    {
        // Nothing to undo: the records were already pointed at the one school.
    }
};
