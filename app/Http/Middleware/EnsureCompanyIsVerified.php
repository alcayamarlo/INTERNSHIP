<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unverified companies can sign in and complete their profile, but cannot post internships,
 * browse students, or send invitations until an admin verifies them.
 */
class EnsureCompanyIsVerified
{
    public const MESSAGE = 'Your company is waiting for admin verification. You can complete your profile meanwhile.';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->employer?->isVerified()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => self::MESSAGE], 403);
        }

        return redirect()->route('employer.dashboard')->with('error', self::MESSAGE);
    }
}
