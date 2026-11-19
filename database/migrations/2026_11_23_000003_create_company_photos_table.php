<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Office and building pictures of a company (a row of `employers`). Shown as a gallery on the company's own
 * profile and on the company's internship page students see (App\Models\CompanyPhoto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('employers')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('caption', 160)->nullable();
            $table->timestamps();

            $table->index('employer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_photos');
    }
};
