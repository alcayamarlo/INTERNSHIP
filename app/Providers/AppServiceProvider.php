<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Certificate;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Portfolio;
use App\Models\Student;
use App\Models\StudentCompetency;
use App\Observers\DashboardCacheObserver;
use App\Policies\MessagePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One collector per request / queue job, so live pings never leak between them.
        $this->app->scoped(\App\Services\LiveUpdates::class);
    }

    public function boot(): void
    {
        // IP addresses are stored only as keyed hashes (App\Support\IpHash). A live site without the
        // key would silently stop recording them, so fail loudly instead.
        if ($this->app->isProduction() && blank(config('privacy.ip_hash_key'))) {
            throw new \RuntimeException('IP_HASH_KEY is not set. Add it to .env (see .env.example).');
        }
        // The session table stores the hash, not the address (replaces the built-in database driver).
        Session::extend('database', function ($app) {
            $config = $app['config'];

            return new \App\Session\HashedIpSessionHandler(
                $app['db']->connection($config->get('session.connection')),
                $config->get('session.table'),
                $config->get('session.lifetime'),
                $app
            );
        });
        // Live refresh pings for the lists people keep open (see App\Services\LiveUpdates).
        foreach ([InternshipApplication::class, \App\Models\Placement::class, \App\Models\AttendanceLog::class, \App\Models\JournalEntry::class, \App\Models\Evaluation::class] as $liveModel) {
            $liveModel::observe(\App\Observers\LiveUpdateObserver::class);
        }

        User::observe(DashboardCacheObserver::class);
        Student::observe(DashboardCacheObserver::class);
        Internship::observe(DashboardCacheObserver::class);
        InternshipApplication::observe(DashboardCacheObserver::class);
        StudentCompetency::observe(DashboardCacheObserver::class);
        Certificate::observe(DashboardCacheObserver::class);
        Portfolio::observe(DashboardCacheObserver::class);

        RateLimiter::for('login', function (Request $request) {
            // The sign-in field is "login" (email or school ID). Keying on it keeps students on the
            // same campus Wi-Fi from sharing one IP-wide limit.
            $login = mb_strtolower(trim((string) $request->input('login', $request->input('email'))));

            return Limit::perMinute(5)->by($login.'|'.\App\Support\IpHash::limiterKey($request->ip()));
        });

        // Burst guard for the first-login password form; the 5-wrong-guesses lockout lives in the controller.
        RateLimiter::for('password-force-change', fn (Request $request) => Limit::perMinute(10)->by('password-force-change:'.($request->user()?->id ?: \App\Support\IpHash::limiterKey($request->ip()))));

        RateLimiter::for('password-reset-request', function (Request $request) {
            $email = hash('sha256', mb_strtolower(trim((string) $request->input('email'))));

            return [
                Limit::perMinute(3)->by('password-reset-email:'.$email),
                Limit::perMinute(10)->by('password-reset-ip:'.\App\Support\IpHash::limiterKey($request->ip())),
            ];
        });

        RateLimiter::for('password-reset-submit', function (Request $request) {
            $email = hash('sha256', mb_strtolower(trim((string) $request->input('email'))));

            return [
                Limit::perMinute(10)->by('password-reset-submit-email:'.$email),
                Limit::perMinute(20)->by('password-reset-submit-ip:'.\App\Support\IpHash::limiterKey($request->ip())),
            ];
        });

        // Named limiters keep their own counter. (A plain throttle:N,M shares one counter per IP or user with every
        // other plain throttle, so a busy route would use up another route's allowance.)
        $pendingTwoFactor = fn (Request $request) => $request->session()->get('two_factor.pending.user_id') ?: \App\Support\IpHash::limiterKey($request->ip());
        RateLimiter::for('two-factor-verify', fn (Request $request) => Limit::perMinute(10)->by('verify|'.$pendingTwoFactor($request)));
        RateLimiter::for('two-factor-resend', fn (Request $request) => Limit::perMinute(3)->by('resend|'.$pendingTwoFactor($request)));
        RateLimiter::for('account-security', fn (Request $request) => Limit::perMinute(5)->by('security|'.($request->user()?->id ?: \App\Support\IpHash::limiterKey($request->ip()))));
        RateLimiter::for('privacy-requests', fn (Request $request) => Limit::perMinute(10)->by('privacy|'.($request->user()?->id ?: \App\Support\IpHash::limiterKey($request->ip()))));
        RateLimiter::for('session-keep-alive', fn (Request $request) => Limit::perMinute(30)->by('keep-alive|'.($request->user()?->id ?: \App\Support\IpHash::limiterKey($request->ip()))));

        RateLimiter::for('content-reports', fn (Request $request) => Limit::perHour(20)->by('content-reports:'.($request->user()?->id ?: \App\Support\IpHash::limiterKey($request->ip()))));

        RateLimiter::for('photo-uploads', fn (Request $request) => Limit::perMinute(10)->by('photo-uploads:'.($request->user()?->id ?: \App\Support\IpHash::limiterKey($request->ip()))));

        // Burst guard on top of the daily invitation limit in InvitationService.
        RateLimiter::for('invitations', fn (Request $request) => Limit::perMinute(10)->by('invitations:'.($request->user()?->id ?: \App\Support\IpHash::limiterKey($request->ip()))));

        // Guest company-invitation pages: opening the link and submitting the form.
        RateLimiter::for('company-invitation', fn (Request $request) => $request->isMethod('post')
            ? Limit::perMinute(5)->by('company-invitation-submit:'.\App\Support\IpHash::limiterKey($request->ip()))
            : Limit::perMinute(20)->by('company-invitation:'.\App\Support\IpHash::limiterKey($request->ip())));
        // Coordinators sending or resending company invitations.
        RateLimiter::for('company-invitation-send', fn (Request $request) => Limit::perMinute(10)->by('company-invitation-send:'.($request->user()?->id ?: \App\Support\IpHash::limiterKey($request->ip()))));

        Gate::define('message.send', function (User $user, User $recipient) {
            return app(MessagePolicy::class)->send($user, $recipient);
        });

        Gate::define('message.view', function (User $user, User $recipient) {
            return app(MessagePolicy::class)->view($user, $recipient);
        });
    }
}
