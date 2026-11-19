<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every test starts by building the application, before RefreshDatabase runs `migrate:fresh`. Stop right here
     * unless the database is a throw-away in-memory SQLite one: a stray DB_CONNECTION=mysql in the shell (PHPUnit's
     * <env> values do not override variables that are already set) must never reach the real database.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        static::assertDisposableDatabase((array) $app['config']->get('database'));

        return $app;
    }

    /** @param  array<string, mixed>  $config  the `database` config array */
    public static function assertDisposableDatabase(array $config): void
    {
        $default = (string) ($config['default'] ?? '');
        $connection = (array) ($config['connections'][$default] ?? []);
        $database = (string) ($connection['database'] ?? '');

        if (($connection['driver'] ?? null) !== 'sqlite' || ! in_array($database, [':memory:', 'file::memory:'], true)) {
            throw new RuntimeException(
                "Refusing to run tests: the database connection [{$default}] is not an in-memory SQLite database "
                .'(driver: '.($connection['driver'] ?? '?').', database: '.($database !== '' ? $database : '?').'). '
                .'Tests run migrate:fresh and would erase it. Check DB_CONNECTION and DB_DATABASE in your shell and phpunit.xml.'
            );
        }
    }
}
