<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Company verification (set by an admin) and private candidate shortlists.
 * Companies that already exist are marked verified so none is locked out on deploy; new ones start unverified.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employers', function (Blueprint $table): void {
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('employers')->whereNull('verified_at')->update(['verified_at' => now()]);

        Schema::create('employer_shortlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->unique(['employer_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_shortlists');
        Schema::table('employers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('verified_by_user_id');
            $table->dropColumn('verified_at');
        });
    }
};
