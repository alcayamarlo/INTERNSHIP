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

        if ($user && $user->must_change_password) {
            // Order: add and verify the email first, then set the new password.
            $target = $user->hasVerifiedEmail() ? 'password.force-change' : 'verification.notice';
            $allowed = $user->hasVerifiedEmail()
                ? ['password.force-change', 'password.force-change.update', 'logout']
                : ['verification.*', 'logout'];

            if (! $request->routeIs(...$allowed)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $user->hasVerifiedEmail() ? 'Set a new password before continuing.' : 'Verify your email before continuing.',
                        'redirect' => route($target),
                    ], 403);
                }

                return redirect()->route($target);
            }
        }

        return $next($request);
    }
}
