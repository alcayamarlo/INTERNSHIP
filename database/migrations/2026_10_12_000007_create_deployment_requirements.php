<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployment_requirements', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('upload_source', 16)->default('student');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('portfolios', function (Blueprint $table): void {
            $table->foreignId('deployment_requirement_id')
                ->nullable()
                ->after('category')
                ->constrained('deployment_requirements')
                ->nullOnDelete();
        });

        $now = now();
        DB::table('deployment_requirements')->insert([
            ['code' => 'resume', 'name' => 'Resume', 'upload_source' => 'system', 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'certificate-of-registration', 'name' => 'Certificate of registration (COR)', 'upload_source' => 'student', 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'parent-guardian-consent', 'name' => 'Parent or guardian consent / waiver', 'upload_source' => 'student', 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'medical-certificate', 'name' => 'Medical certificate', 'upload_source' => 'student', 'sort_order' => 4, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'endorsement-letter', 'name' => 'Endorsement letter', 'upload_source' => 'coordinator', 'sort_order' => 5, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'signed-moa', 'name' => 'Signed MOA / training agreement', 'upload_source' => 'coordinator', 'sort_order' => 6, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('portfolios')
            ->where('category', 'deployment_document')
            ->whereRaw('LOWER(title) = ?', ['endorsement letter'])
            ->update(['deployment_requirement_id' => DB::table('deployment_requirements')->where('code', 'endorsement-letter')->value('id')]);
        DB::table('portfolios')
            ->where('category', 'deployment_document')
            ->whereRaw('LOWER(title) IN (?, ?, ?, ?)', [
                'memorandum of agreement',
                'moa',
                'signed moa',
                'signed moa / training agreement',
            ])
            ->update(['deployment_requirement_id' => DB::table('deployment_requirements')->where('code', 'signed-moa')->value('id')]);
    }

    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('deployment_requirement_id');
        });

        Schema::dropIfExists('deployment_requirements');
    }
};
