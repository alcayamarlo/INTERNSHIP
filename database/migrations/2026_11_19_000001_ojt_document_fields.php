<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Student data on the printed OJT forms:
 * - academic_settings.ojt_departments: the one "Designation" dropdown list (admin-managed), so the
 *   department is spelled the same on every document.
 * - placements.supervisor_name: the supervisor printed on this OJT's forms (defaults to the company's
 *   contact person when blank).
 * - header_snapshot on each document: name, student ID, course, company, supervisor and designation as
 *   they were when the document was submitted, so later profile edits never change it.
 * - journal_entries.header_overrides: which header fields the student changed on that one report, by
 *   whom and when.
 * No timestamp columns are added (MariaDB's implicit ON UPDATE only affects NOT NULL timestamps).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_settings', function (Blueprint $table) {
            $table->json('ojt_departments')->nullable()->after('journal_due_days');
        });
        Schema::table('placements', function (Blueprint $table) {
            $table->string('supervisor_name', 80)->nullable()->after('department');
            $table->json('certificate_snapshot')->nullable()->after('certificate_number');
        });
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->json('header_snapshot')->nullable()->after('daily_snapshot');
            $table->json('header_overrides')->nullable()->after('header_snapshot');
        });
        Schema::table('appraisals', function (Blueprint $table) {
            $table->json('header_snapshot')->nullable()->after('period_end');
        });
        Schema::table('evaluations', function (Blueprint $table) {
            $table->json('header_snapshot')->nullable()->after('form_version');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', fn (Blueprint $table) => $table->dropColumn('header_snapshot'));
        Schema::table('appraisals', fn (Blueprint $table) => $table->dropColumn('header_snapshot'));
        Schema::table('journal_entries', fn (Blueprint $table) => $table->dropColumn(['header_snapshot', 'header_overrides']));
        Schema::table('placements', fn (Blueprint $table) => $table->dropColumn(['supervisor_name', 'certificate_snapshot']));
        Schema::table('academic_settings', fn (Blueprint $table) => $table->dropColumn('ojt_departments'));
    }
};
