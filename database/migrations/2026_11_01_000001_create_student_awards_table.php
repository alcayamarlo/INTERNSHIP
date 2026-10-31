<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per honor or award. The old students.honors_awards paragraph is kept as is, and a copy of
 * it becomes a single "Other" entry so nothing is lost. It is not split into separate awards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_awards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('level', 20);
            // Text, not string: the copied old paragraph can be up to 2000 characters.
            $table->text('title');
            $table->string('event', 150)->nullable();
            $table->string('year', 20)->nullable();
            $table->string('detail', 150)->nullable();
            $table->timestamps();
            $table->index(['student_id', 'level']);
        });

        $now = now();
        DB::table('students')
            ->whereNotNull('honors_awards')
            ->where('honors_awards', '<>', '')
            ->orderBy('id')
            ->select(['id', 'honors_awards'])
            ->chunkById(200, function ($students) use ($now): void {
                $rows = $students
                    ->filter(fn ($student) => trim((string) $student->honors_awards) !== '')
                    ->map(fn ($student) => [
                        'student_id' => $student->id,
                        'level' => 'other',
                        'title' => trim($student->honors_awards),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->values()
                    ->all();

                if ($rows !== []) {
                    DB::table('student_awards')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        // The old honors_awards column was never changed, so dropping the table loses nothing.
        Schema::dropIfExists('student_awards');
    }
};
