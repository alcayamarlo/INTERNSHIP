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

        $profileExists = match ($user->role) {
            UserRole::Student => $user->student()->exists(),
            UserRole::Employer => $user->employer()->exists(),
            UserRole::Coordinator => $user->coordinator()->exists(),
            UserRole::Admin => true,
        };

        $profileRoute = match ($user->role) {
            UserRole::Student => $request->routeIs('student.profile.edit', 'student.profile.update'),
            UserRole::Employer => $request->routeIs('employer.profile.edit', 'employer.profile.update'),
            UserRole::Coordinator => $request->routeIs('coordinator.profile.edit', 'coordinator.profile.update'),
            UserRole::Admin => false,
        };

        if (! $profileExists && ! $profileRoute) {
            return redirect()->route('profile.complete');
        }

        return $next($request);
    }
}
