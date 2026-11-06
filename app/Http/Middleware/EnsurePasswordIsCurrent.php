<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: while must_change_password is set (first login with the birthday password), every
 * protected page and endpoint is blocked, including direct URLs and background (JSON) requests.
 */
class EnsurePasswordIsCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $request->routeIs('password.force-change', 'password.force-change.update', 'logout')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Set a new password before continuing.',
                    'redirect' => route('password.force-change'),
                ], 403);
            }

            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
