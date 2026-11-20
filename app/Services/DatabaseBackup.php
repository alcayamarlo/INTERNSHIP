<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

/** Dumps the MySQL database to a file. Shared by the nightly job and the admin Backup button. */
class DatabaseBackup
{
    /** @throws RuntimeException when the dump fails; no partial file is left behind. */
    public function dumpTo(string $path): void
    {
        $config = config('database.connections.mysql', []);

        if (empty($config['database'])) {
            throw new RuntimeException('The MySQL database name is not configured.');
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $binary = (string) config('database.mysqldump_path');
        if ($binary !== '' && (str_contains($binary, '/') || str_contains($binary, '\\')) && ! is_file($binary)) {
            throw new RuntimeException("mysqldump was not found at {$binary}. Set MYSQLDUMP_PATH in .env to its full path (XAMPP: C:/xampp/mysql/bin/mysqldump.exe).");
        }

        // Credentials go in a private file so the password never appears in the process list.
        $credentials = tempnam(sys_get_temp_dir(), 'dump');
        chmod($credentials, 0600);
        file_put_contents($credentials, $this->optionFile($config));

        try {
            $process = new Process([
                config('database.mysqldump_path'),
                '--defaults-extra-file='.$credentials,
                '--single-transaction', // a consistent snapshot without locking the tables
                '--routines',
                '--default-character-set=utf8mb4',
                '--result-file='.$path,
                $config['database'],
            ]);
            $process->setTimeout(280);
            $process->setEnv($this->environment());
            $process->run();

            if (! $process->isSuccessful() || ! is_file($path) || filesize($path) === 0) {
                $why = trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'no message (exit code '.$process->getExitCode().')';
                throw new RuntimeException('mysqldump failed: '.mb_substr($why, 0, 400));
            }
        } catch (\Throwable $exception) {
            if (is_file($path)) {
                unlink($path);
            }

            throw $exception instanceof RuntimeException
                ? $exception
                : new RuntimeException('mysqldump could not run: '.$exception->getMessage(), 0, $exception);
        } finally {
            unlink($credentials);
        }
    }

    /**
     * The environment mysqldump runs in: everything this process has, plus SystemRoot on Windows. Without SystemRoot
     * Windows cannot open a network socket ("Can't create TCP/IP socket (10106)"), which is what happens when the dump is
     * started from a web server: the same command works in a terminal, because a terminal always has it.
     *
     * @return array<string, string>
     */
    private function environment(): array
    {
        $env = array_map('strval', getenv() ?: []);
        if (PHP_OS_FAMILY === 'Windows') {
            $root = getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: ($_SERVER['SystemRoot'] ?? $_SERVER['SYSTEMROOT'] ?? null) ?: 'C:\\Windows';
            $env['SystemRoot'] = $root;
            $env['windir'] = $env['windir'] ?? $root;
        }

        return $env;
    }

    private function optionFile(array $config): string
    {
        $lines = ['[client]'];

        foreach ([
            'user' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? '3306',
        ] as $option => $value) {
            if ((string) $value !== '') {
                $lines[] = $option.'="'.addcslashes((string) $value, '\\"').'"';
            }
        }

        return implode("\n", $lines)."\n";
    }
}
