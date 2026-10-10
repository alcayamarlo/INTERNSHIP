<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SecureExistingEvidenceFiles extends Command
{
    protected $signature = 'files:secure-existing {--remove-public : Delete public copies after every referenced file is verified on the private disk}';

    protected $description = 'Copy existing evidence, resumes, and reports to private storage';

    public function handle(): int
    {
        $paths = collect([
            DB::table('student_competencies')->whereNotNull('evidence_path')->pluck('evidence_path'),
            DB::table('certificates')->whereNotNull('file_path')->pluck('file_path'),
            DB::table('portfolios')->whereNotNull('file_path')->pluck('file_path'),
            DB::table('resumes')->whereNotNull('file_path')->pluck('file_path'),
            DB::table('reports')->whereNotNull('file_path')->pluck('file_path'),
        ])->flatten()->filter()->unique()->values();

        $public = Storage::disk('public');
        $private = Storage::disk('private');
        $errors = [];

        foreach ($paths as $path) {
            if (str_contains($path, '..') || str_starts_with($path, '/')) {
                $errors[] = "Unsafe stored path: {$path}";
                continue;
            }

            $publicExists = $public->exists($path);
            $privateExists = $private->exists($path);

            if (! $publicExists && ! $privateExists) {
                $errors[] = "File is missing from both disks: {$path}";
                continue;
            }

            if (! $privateExists || ($publicExists && ! $this->filesMatch($public, $private, $path))) {
                if (! $publicExists) {
                    $errors[] = "Cannot migrate missing public file: {$path}";
                    continue;
                }

                $stream = $public->readStream($path);
                if ($stream === false) {
                    $errors[] = "Cannot read public file: {$path}";
                    continue;
                }

                try {
                    $private->writeStream($path, $stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            }

            if (! $private->exists($path) || ($publicExists && ! $this->filesMatch($public, $private, $path))) {
                $errors[] = "Private copy verification failed: {$path}";
            }
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($this->option('remove-public')) {
            foreach ($paths as $path) {
                $public->delete($path);
            }
        }

        $this->info('Verified '.$paths->count().' referenced files on private storage.');
        if (! $this->option('remove-public')) {
            $this->warn('Public copies were retained. Re-run with --remove-public only after verifying the private copies.');
        }

        return self::SUCCESS;
    }

    private function filesMatch($source, $destination, string $path): bool
    {
        $sourceChecksum = $this->checksum($source, $path);
        $destinationChecksum = $this->checksum($destination, $path);

        return $sourceChecksum !== null
            && $destinationChecksum !== null
            && hash_equals($sourceChecksum, $destinationChecksum);
    }

    private function checksum($disk, string $path): ?string
    {
        $stream = $disk->readStream($path);
        if ($stream === false) {
            return null;
        }

        $context = hash_init('sha256');
        try {
            hash_update_stream($context, $stream);
        } finally {
            fclose($stream);
        }

        return hash_final($context);
    }
}