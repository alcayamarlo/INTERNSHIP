<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\InternshipApplication;
use App\Models\Placement;
use App\Models\Portfolio;
use App\Models\Resume;
use App\Models\StudentCompetency;
use App\Models\SystemLog;
use App\Models\User;
use App\Support\UploadDisks;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/** Platform usage (people, activity, uploads, storage) and system health (database, queue, scheduler, errors). */
class HealthController extends Controller
{
    public function index()
    {
        return view('admin.system.health', [
            'usage' => $this->usage(),
            'health' => $this->health(),
        ]);
    }

    private function usage(): array
    {
        $since30 = now()->subDays(30);
        $signIns = SystemLog::query()
            ->where('action', 'login')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return [
            'users_by_role' => collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [
                $role->label() => User::query()->where('role', $role->value)->whereNull('archived_at')->count(),
            ])->all(),
            'active_7' => User::query()->where('last_login_at', '>=', now()->subDays(7))->count(),
            'active_30' => User::query()->where('last_login_at', '>=', $since30)->count(),
            'new_accounts_30' => User::query()->where('created_at', '>=', $since30)->count(),
            'applications_30' => InternshipApplication::query()->where('created_at', '>=', $since30)->count(),
            'active_placements' => Placement::query()->where('status', Placement::STATUS_ACTIVE)->count(),
            'uploads_30' => Portfolio::query()->where('created_at', '>=', $since30)->count()
                + Certificate::query()->where('created_at', '>=', $since30)->count()
                + StudentCompetency::query()->where('created_at', '>=', $since30)->whereNotNull('evidence_path')->count()
                + Resume::query()->where('created_at', '>=', $since30)->count(),
            'sign_ins' => collect(range(13, 0))->mapWithKeys(function (int $daysAgo) use ($signIns) {
                $day = now()->subDays($daysAgo)->toDateString();

                return [$day => (int) ($signIns[$day] ?? 0)];
            })->all(),
            // Walking the upload folders is slow, so the total is cached for 10 minutes.
            'storage_bytes' => Cache::remember('admin:storage-bytes', 600, fn () => collect([UploadDisks::public(), UploadDisks::private()])
                ->unique()
                ->sum(function (string $disk) {
                    try {
                        return collect(Storage::disk($disk)->allFiles())->sum(fn (string $path) => Storage::disk($disk)->size($path));
                    } catch (\Throwable) {
                        return 0;
                    }
                })),
        ];
    }

    private function health(): array
    {
        $started = microtime(true);
        try {
            DB::select('select 1');
            $database = ['ok' => true, 'detail' => DB::connection()->getDriverName().' · '.round((microtime(true) - $started) * 1000, 1).' ms'];
        } catch (\Throwable $exception) {
            $database = ['ok' => false, 'detail' => 'Not reachable: '.$exception->getMessage()];
        }

        $lastRun = Cache::get('scheduler:last_run');
        $lastRunAt = $lastRun ? Carbon::parse($lastRun) : null;

        return [
            'database' => $database,
            'queue' => [
                'driver' => config('queue.default'),
                'pending' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : null,
                'failed' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null,
            ],
            'scheduler' => [
                'last_run' => $lastRunAt,
                'ok' => $lastRunAt !== null && $lastRunAt->gt(now()->subMinutes(5)),
            ],
            'errors' => $this->recentErrors(),
            'environment' => [
                'PHP' => PHP_VERSION,
                'Laravel' => app()->version(),
                'GD (photos)' => extension_loaded('gd') ? 'Enabled' : 'Missing',
                'Free disk space' => $this->bytes((int) @disk_free_space(storage_path())),
                'Timezone' => config('app.timezone'),
            ],
        ];
    }

    /** The latest error lines from the application log (newest first). */
    private function recentErrors(): array
    {
        $path = storage_path('logs/laravel.log');
        if (! is_readable($path)) {
            return [];
        }

        $size = filesize($path);
        $handle = fopen($path, 'rb');
        fseek($handle, max(0, $size - 262144));
        $tail = (string) stream_get_contents($handle);
        fclose($handle);

        preg_match_all('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.*)$/m', $tail, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->map(fn (array $match) => ['at' => $match[1], 'level' => $match[2], 'message' => mb_strimwidth($match[3], 0, 220, '…')])
            ->reverse()
            ->take(10)
            ->values()
            ->all();
    }

    public static function bytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }
}
