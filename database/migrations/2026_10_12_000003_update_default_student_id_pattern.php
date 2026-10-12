<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('academic_settings')
            ->where('student_id_pattern', '^\\d{4}-\\d{5}$')
            ->update(['student_id_pattern' => '^SCC-\\d{2}-\\d{8}$']);
    }

    public function down(): void
    {
        DB::table('academic_settings')
            ->where('student_id_pattern', '^SCC-\\d{2}-\\d{8}$')
            ->update(['student_id_pattern' => '^\\d{4}-\\d{5}$']);
    }
};
