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

        return $next($request);
    }
}
