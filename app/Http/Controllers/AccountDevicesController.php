<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Support\IpHash;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** "Your devices": where this account is signed in, and a way to sign out everywhere else. */
class AccountDevicesController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function index(Request $request)
    {
        $current = $request->session()->getId();
        $devices = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($row) => [
                'current' => hash_equals((string) $current, (string) $row->id),
                'device' => self::describe((string) $row->user_agent),
                'ip' => IpHash::label($row->ip_address),
                'last_active' => Carbon::createFromTimestamp((int) $row->last_activity),
            ]);

        return view('account.devices', [
            'devices' => $devices,
            'tracked' => config('session.driver') === 'database',
            'idleMinutes' => \App\Http\Middleware\EndIdleSession::limitFor($request->user()),
        ]);
    }

    /** Signs out every other browser of this account. Asks for the password, as a stolen session must not do this. */
    public function destroyOthers(Request $request)
    {
        $request->validate(
            ['password' => ['required', 'current_password']],
            ['password.current_password' => 'That is not your current password.']
        );

        $removed = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '<>', $request->session()->getId())
            ->delete();
        $request->user()->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
        app(\App\Services\TwoFactor::class)->forgetBrowsers($request->user()); // trusted browsers are devices too
        $this->activityLog->log($request->user(), 'sessions_revoked', ['sessions' => $removed]);

        return redirect()->route('account.devices')->with('success', $removed === 0
            ? 'You were not signed in anywhere else.'
            : 'Signed out of '.$removed.' other '.Str::plural('device', $removed).'.');
    }

    /** "Chrome on Windows" from a user-agent string; good enough to recognise one's own devices. */
    public static function describe(string $agent): string
    {
        if ($agent === '') {
            return 'Unknown device';
        }
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') || str_contains($agent, 'CriOS') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Browser',
        };
        $system = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iPhone or iPad',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS X') || str_contains($agent, 'Macintosh') => 'Mac',
            str_contains($agent, 'CrOS') => 'Chromebook',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'an unknown system',
        };

        return $browser.' on '.$system;
    }
}
