<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Serverless writable-path defaults (Vercel only)
|--------------------------------------------------------------------------
|
| Vercel function filesystems are read-only except /tmp. Laravel writes its
| config/route/package/view caches and (optionally) logs to paths below the
| project root by default, which throws on Vercel. Default those to /tmp
| unless the environment explicitly overrides them (e.g. in the dashboard).
|
*/
foreach ([
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_ROUTES_CACHE' => '/tmp/routes.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/views',
] as $key => $default) {
    if (getenv($key) === false) {
        putenv($key.'='.$default);
        $_ENV[$key] = $default;
    }
}

if (getenv('LOG_CHANNEL') === false) {
    putenv('LOG_CHANNEL=stderr');
    $_ENV['LOG_CHANNEL'] = 'stderr';
}

// Register the Composer autoloader...
require dirname(__DIR__).'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once dirname(__DIR__).'/bootstrap/app.php';

$app->handleRequest(Request::capture());
