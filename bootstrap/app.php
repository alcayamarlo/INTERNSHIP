<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require __DIR__.'/../routes/health.php';
            require __DIR__.'/../routes/private-files.php';
            require __DIR__.'/../routes/public-files.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\NormalizeEmailInput::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Attach request and user details to every logged exception so a
        // production "Server Error" can be traced in the Railway logs.
        $exceptions->context(function (): array {
            $request = request();
            $user = $request->user();

            return array_filter([
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => $user?->id,
                'role' => $user?->role?->value,
            ], fn ($value) => $value !== null);
        });
    })->create();
