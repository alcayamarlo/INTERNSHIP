<?php

use App\Enums\UserRole;
use App\Jobs\ScheduledBackup;
use App\Models\User;
use App\Services\DatabaseBackup;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('applies the forced password change to every verified route', function () {
    $missing = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('verified', $route->gatherMiddleware(), true))
        ->reject(fn ($route) => in_array('password.current', $route->gatherMiddleware(), true))
        ->map(fn ($route) => $route->uri())
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

it('only logs out through POST', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Student]))
        ->get('/logout')
        ->assertMethodNotAllowed();

    $this->assertAuthenticated();
});

it('does not create demo accounts in production', function () {
    app()->detectEnvironment(fn () => 'production');

    app(DatabaseSeeder::class)->run();

    expect(User::where('email', 'admin@skillbridge.test')->exists())->toBeFalse();
});

it('reports a failed dump and leaves no partial file', function () {
    config(['database.connections.mysql.database' => 'nexus', 'database.mysqldump_path' => base_path('no-such-mysqldump')]);
    $path = storage_path('framework/testing/backup-should-not-exist.sql');

    expect(fn () => app(DatabaseBackup::class)->dumpTo($path))->toThrow(RuntimeException::class)
        ->and(file_exists($path))->toBeFalse();
});

it('shows an error on the admin backup action when mysqldump fails', function () {
    config(['database.connections.mysql.database' => 'nexus', 'database.mysqldump_path' => base_path('no-such-mysqldump')]);

    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->post(route('admin.backup'))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('shows recent errors from dated daily log files on the health page', function () {
    $storage = storage_path('framework/testing/health-'.uniqid());
    mkdir($storage.'/logs', 0755, true);
    file_put_contents($storage.'/logs/laravel-2026-11-08.log', "[2026-11-08 09:00:00] local.ERROR: oldest-daily-error\n");
    file_put_contents($storage.'/logs/laravel-2026-11-09.log', "[2026-11-09 09:00:00] local.ERROR: middle-daily-error\n");
    file_put_contents($storage.'/logs/laravel-2026-11-10.log', "[2026-11-10 09:00:00] local.ERROR: newest-daily-error\n");
    touch($storage.'/logs/laravel-2026-11-08.log', now()->subDays(2)->getTimestamp());
    touch($storage.'/logs/laravel-2026-11-09.log', now()->subDay()->getTimestamp());
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    app()->useStoragePath($storage);

    try {
        $this->actingAs($admin)->get(route('admin.health'))
            ->assertOk()
            ->assertSee('newest-daily-error')
            ->assertSee('middle-daily-error')
            ->assertDontSee('oldest-daily-error');
    } finally {
        app()->useStoragePath(dirname($storage, 3));
        File::deleteDirectory($storage);
    }
});

it('keeps only the newest 30 backups in the folder it writes to', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');

    foreach (range(1, 31) as $day) {
        $file = sprintf('backups/backup_old_%02d.sql', $day);
        $disk->put($file, 'dump');
        touch($disk->path($file), now()->subDays(40 - $day)->getTimestamp());
    }

    $this->mock(DatabaseBackup::class, fn ($mock) => $mock->shouldReceive('dumpTo')->once()->andReturnUsing(
        fn (string $path) => file_put_contents($path, 'new dump')
    ));

    app()->call([new ScheduledBackup, 'handle']);

    $files = $disk->files('backups');
    expect($files)->toHaveCount(30)
        ->and($files)->not->toContain('backups/backup_old_01.sql', 'backups/backup_old_02.sql')
        ->and($files)->toContain('backups/backup_old_31.sql');
});
