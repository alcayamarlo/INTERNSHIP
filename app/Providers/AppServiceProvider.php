<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\MessagePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('password', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(3)->by($email.$request->ip());
        });

        Gate::define('message.send', function (User $user, User $recipient) {
            return app(MessagePolicy::class)->send($user, $recipient);
        });
    }
}
