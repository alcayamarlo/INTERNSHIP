<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Competencies become "certificates" in the UI. Additive and reversible: the legacy
 * `category` and `assessment_name` columns are left untouched, so down() loses nothing
 * that existed before this migration.
 */
return new class extends Migration
{
    /** legacy category => [certificate_type, skill_type] */
    private const CATEGORY_MAP = [
        'certification' => ['certificate', 'technical'],
        'training' => ['training', 'technical'],
        'seminar' => ['seminar', 'technical'],
        'workshop' => ['workshop', 'technical'],
        'skill' => ['certificate', 'technical'],
        'technical' => ['certificate', 'technical'],
        'soft' => ['certificate', 'soft'],
    ];

    public function up(): void
    {
        Schema::table('student_competencies', function (Blueprint $table): void {
            $table->string('certificate_type', 20)->nullable()->after('category');
            $table->string('skill_type', 20)->nullable()->after('certificate_type');
        });

        Schema::create('student_competency_skills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_competency_id')->constrained('student_competencies')->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('name_normalized', 60);
            $table->timestamps();
            $table->unique(['student_competency_id', 'name_normalized'], 'competency_skills_unique');
        });

        // Old Documents-tab certificates are completed into a certificate record via the wizard;
        // the original row is kept and linked so nothing is lost.
        Schema::table('certificates', function (Blueprint $table): void {
            $table->foreignId('student_competency_id')->nullable()->constrained('student_competencies')->nullOnDelete();
        });

        $now = now();
        DB::table('student_competencies')->orderBy('id')->each(function (object $competency) use ($now): void {
            [$type, $skillType] = self::CATEGORY_MAP[$competency->category] ?? ['certificate', 'technical'];
            DB::table('student_competencies')->where('id', $competency->id)->update([
                'certificate_type' => $type,
                'skill_type' => $skillType,
            ]);

            $credential = preg_replace('/\s+/u', ' ', trim((string) $competency->assessment_name));
            if ($credential !== '') {
                DB::table('student_competency_skills')->insert([
                    'student_competency_id' => $competency->id,
                    'name' => mb_substr($credential, 0, 60),
                    'name_normalized' => mb_strtolower(mb_substr($credential, 0, 60)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('student_competency_id');
        });
        Schema::dropIfExists('student_competency_skills');
        Schema::table('student_competencies', function (Blueprint $table): void {
            $table->dropColumn(['certificate_type', 'skill_type']);
        });
    }
};
