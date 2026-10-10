<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    $checks = [
        'database' => fn () => \DB::connection()->getPdo() !== null,
        'cache' => function () {
            $key = '__health_check_'.bin2hex(random_bytes(8));

            try {
                \Cache::put($key, 'ok', 5);

                return \Cache::get($key) === 'ok';
            } finally {
                \Cache::forget($key);
            }
        },
        'queue' => fn () => \Queue::connection()->size() >= 0,
    ];

    $status = 'healthy';
    $details = [];

    foreach ($checks as $name => $check) {
        try {
            if ($check() === false) {
                throw new \RuntimeException('Health check returned false.');
            }

            $details[$name] = 'ok';
        } catch (\Throwable $exception) {
            $status = 'unhealthy';
            $details[$name] = 'unavailable';
            \Log::warning('Application health check failed.', [
                'check' => $name,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    return response()->json([
        'status' => $status,
        'timestamp' => now()->toIso8601String(),
        'checks' => $details,
    ], $status === 'healthy' ? 200 : 503);
})->name('health');