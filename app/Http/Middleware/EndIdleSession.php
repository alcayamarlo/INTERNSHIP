<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends a signed-in session after config('security.idle_minutes') without activity: 30 minutes for staff, 120 for
 * students. Every request counts as activity except the background ones listed in BACKGROUND_ROUTES (live updates
 * and the idle check itself), so an open tab does not keep a session alive forever. public/js/idle-timeout.js
 * warns a minute before and offers "Stay signed in".
 */
class EndIdleSession
{
    public const SESSION_KEY = 'last_activity_at';

    /** Requests the page makes by itself; they do not count as the person being active. */
    public const BACKGROUND_ROUTES = ['session.idle-status', 'messages.row', 'notifications.unread'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $request->hasSession() || ! config('security.idle_timeout')) {
            return $next($request);
        }

        $limit = self::limitFor($user) * 60;
        $last = (int) $request->session()->get(self::SESSION_KEY, 0);

        if ($last > 0 && now()->getTimestamp() - $last > $limit) {
            app(ActivityLogService::class)->log($user, 'logout_idle', ['minutes' => intdiv($limit, 60)]);
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $message = 'You were signed out after '.intdiv($limit, 60).' minutes without activity. Please sign in again.';

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'signed_out' => true], 401)
                : redirect()->guest(route('login'))->with('status', $message);
        }

        if ($last === 0 || ! in_array($request->route()?->getName(), self::BACKGROUND_ROUTES, true)) {
            $request->session()->put(self::SESSION_KEY, now()->getTimestamp());
        }

        return $next($request);
    }

    public static function limitFor(User $user): int
    {
        $minutes = (array) config('security.idle_minutes');

        return max(1, (int) ($user->isRole(UserRole::Student) ? ($minutes['student'] ?? 120) : ($minutes['staff'] ?? 30)));
    }

    /** Seconds left before this session ends, for the warning in the page. */
    public static function secondsLeft(Request $request): int
    {
        $last = (int) $request->session()->get(self::SESSION_KEY, now()->getTimestamp());

        return max(0, self::limitFor($request->user()) * 60 - (now()->getTimestamp() - $last));
    }
}
