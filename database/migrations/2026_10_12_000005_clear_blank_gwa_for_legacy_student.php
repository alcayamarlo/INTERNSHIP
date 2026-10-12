<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')
            ->where('student_id_number', 'SCC-19-00012320')
            ->where('gwa', 0)
            ->update(['gwa' => null]);
    }

    public function down(): void
    {
    }
};
