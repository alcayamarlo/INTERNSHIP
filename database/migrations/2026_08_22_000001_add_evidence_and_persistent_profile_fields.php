<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('internship_applications')->where('status', 'pending')->update(['status' => 'submitted']);
        Schema::table('students', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('gender')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('department')->nullable();
            $table->date('expected_graduation')->nullable();
            $table->string('preferred_internship_field')->nullable();
            $table->string('preferred_work_setup')->nullable();
            $table->string('preferred_location')->nullable();
        });
        Schema::table('student_competencies', function (Blueprint $table) {
            $table->string('assessment_name')->nullable();
            $table->string('issuing_organization')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('evidence_name')->nullable();
            $table->string('verification_status')->default('evidence_submitted');
        });
        Schema::table('certificates', function (Blueprint $table) {
            $table->date('expiration_date')->nullable();
            $table->string('verification_status')->default('evidence_submitted');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'middle_name', 'suffix', 'gender', 'city', 'province', 'zip_code', 'department', 'expected_graduation', 'preferred_internship_field', 'preferred_work_setup', 'preferred_location']);
        });
        Schema::table('student_competencies', function (Blueprint $table) {
            $table->dropColumn(['assessment_name', 'issuing_organization', 'evidence_path', 'evidence_name', 'verification_status']);
        });
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['expiration_date', 'verification_status']);
        });
    }
};
