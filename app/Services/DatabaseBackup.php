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

        // Credentials go in a private file so the password never appears in the process list.
        $credentials = tempnam(sys_get_temp_dir(), 'dump');
        chmod($credentials, 0600);
        file_put_contents($credentials, $this->optionFile($config));

        try {
            $process = new Process([
                config('database.mysqldump_path'),
                '--defaults-extra-file='.$credentials,
                '--result-file='.$path,
                $config['database'],
            ]);
            $process->setTimeout(280);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($path) || filesize($path) === 0) {
                throw new RuntimeException('mysqldump failed: '.trim($process->getErrorOutput()));
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
