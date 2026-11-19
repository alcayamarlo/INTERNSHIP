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

    // The 6 files of the demo accounts are private; the presentation data adds 350 certificate images on the public disk.
    [$public, $private] = [$paths->filter(fn ($path) => str_starts_with($path, 'certificates/')), $paths->reject(fn ($path) => str_starts_with($path, 'certificates/'))];
    expect($private)->toHaveCount(6)->and($public)->toHaveCount(350);

    // Both seeders (the demo accounts and the presentation data) write personal data through the models: all of it is encrypted.
    expectPersonalDataStoredEncrypted();

    foreach ($private as $path) {
        expect(Storage::disk('private')->exists($path))->toBeTrue();
    }
    foreach ($public as $path) {
        expect(Storage::disk('public')->exists($path))->toBeTrue();
    }
});
