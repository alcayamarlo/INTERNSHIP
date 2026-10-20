<?php

/*
 * Vercel entry point.
 * Vercel's file system is read-only except /tmp, so Laravel's cache,
 * compiled views, sessions and logs are pointed at /tmp.
 * Files saved here do NOT persist between requests (uploads need cloud storage).
 */

$tmp = '/tmp/storage';

foreach (['app/public', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $dir) {
    if (! is_dir("$tmp/$dir")) {
        @mkdir("$tmp/$dir", 0777, true);
    }
}

$paths = [
    'VIEW_COMPILED_PATH'  => "$tmp/framework/views",
    'APP_CONFIG_CACHE'    => '/tmp/config.php',
    'APP_ROUTES_CACHE'    => '/tmp/routes.php',
    'APP_EVENTS_CACHE'    => '/tmp/events.php',
    'APP_SERVICES_CACHE'  => '/tmp/services.php',
    'APP_PACKAGES_CACHE'  => '/tmp/packages.php',
];

foreach ($paths as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->useStoragePath($tmp);

$app->handleRequest(\Illuminate\Http\Request::capture());