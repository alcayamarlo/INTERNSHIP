<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

/**
 * Scans an uploaded file with ClamAV before it is stored.
 *
 * OFF unless CLAMAV_PATH is set (config security.clamav.path), so nothing changes until ClamAV is installed on the server.
 * When it is set the scanner FAILS CLOSED: a file that is infected, or that cannot be scanned for any reason (the program
 * is missing, crashes or times out), is refused. A broken scanner must not become a way past the check.
 *
 * `clamscan` exit codes: 0 clean, 1 infected, anything else an error. `clamdscan` uses the same codes.
 */
class VirusScanner
{
    public function enabled(): bool
    {
        return filled(config('security.clamav.path'));
    }

    /** @throws ValidationException when the file is infected or cannot be scanned (names the form field it came from) */
    public function assertClean(UploadedFile $file, ?string $field = null): void
    {
        if (! $this->enabled()) {
            return;
        }
        $field ??= $this->fieldOf($file);
        $path = (string) config('security.clamav.path');
        $target = $file->getRealPath();

        if (! is_file($path) || $target === false || ! is_readable($target)) {
            $this->refuse($field, 'scanner_unavailable', 'The virus scanner is not available right now, so this file was not accepted. Try again later or tell the administrator.');
        }

        try {
            $process = new Process([$path, ...(array) config('security.clamav.options', []), $target]);
            $process->setTimeout((int) config('security.clamav.timeout', 30));
            $process->run();
        } catch (\Throwable $exception) {
            Log::error('Virus scan could not run', ['error' => $exception->getMessage()]);
            $this->refuse($field, 'scanner_error', 'The virus scanner did not finish, so this file was not accepted. Try again later.');
        }

        $exit = $process->getExitCode();
        if ($exit === 0) {
            return;
        }
        if ($exit === 1) {
            $signature = trim((string) preg_replace('/^.*:\s*(.+?)\s+FOUND.*$/s', '$1', trim($process->getOutput())));
            $this->refuse($field, 'virus_found', 'This file was blocked because it may contain a virus. Scan it on your computer, or upload a different file.', ['signature' => mb_substr($signature, 0, 80)]);
        }
        Log::error('Virus scan failed', ['exit' => $exit, 'error' => mb_substr(trim($process->getErrorOutput()), 0, 300)]);
        $this->refuse($field, 'scanner_error', 'The virus scanner could not check this file, so it was not accepted. Try again later.');
    }

    private function refuse(string $field, string $reason, string $message, array $extra = []): never
    {
        try {
            app(ActivityLogService::class)->log(Auth::user(), 'upload_blocked', ['reason' => $reason] + $extra);
        } catch (\Throwable) {
            // the refusal matters more than its log entry
        }

        throw ValidationException::withMessages([$field => $message]);
    }

    /** The form field the file came in on, so the message shows next to the right input. */
    private function fieldOf(UploadedFile $file): string
    {
        foreach (request()->allFiles() as $name => $value) {
            foreach ((array) $value as $candidate) {
                if ($candidate === $file) {
                    return (string) $name;
                }
            }
        }

        return 'file';
    }
}
