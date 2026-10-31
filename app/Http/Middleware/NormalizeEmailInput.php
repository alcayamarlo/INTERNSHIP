<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeEmailInput
{
    public function handle(Request $request, Closure $next): Response
    {
        if (is_string($email = $request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($email))]);
        }

        // The login form accepts either an email or a school ID; only
        // lowercase/trim it as an email when it actually looks like one,
        // so a school ID's casing/format isn't mangled.
        if (is_string($login = $request->input('login'))) {
            $login = trim($login);
            $request->merge(['login' => str_contains($login, '@') ? mb_strtolower($login) : $login]);
        }

        return $next($request);
    }
}
