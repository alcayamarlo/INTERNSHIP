<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Unauthorized access.');
        }

        $allowed = collect($roles)->map(fn ($role) => UserRole::from($role));

        if (! $user->isRole(...$allowed->all())) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
