<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('creates private files for every file path referenced by the seeded data', function () {
    Storage::fake('private');
    Storage::fake('public');

    $this->seed(DatabaseSeeder::class);

    $paths = collect([
        DB::table('student_competencies')->whereNotNull('evidence_path')->pluck('evidence_path'),
        DB::table('certificates')->whereNotNull('file_path')->pluck('file_path'),
        DB::table('portfolios')->whereNotNull('file_path')->pluck('file_path'),
        DB::table('resumes')->whereNotNull('file_path')->pluck('file_path'),
        DB::table('reports')->whereNotNull('file_path')->pluck('file_path'),
    ])->flatten()->filter()->unique()->values();

    expect($paths)->toHaveCount(6);

    foreach ($paths as $path) {
        expect(Storage::disk('private')->exists($path))->toBeTrue();
    }
});
