<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

/*
 * Boot probe for the benchmark runner (benchmarks/bench.php).
 *
 * Usage: php probe.php <laravel-app-dir>
 *
 * Boots the app as a web request (not a console one) and handles GET / through the
 * HTTP kernel, like public/index.php. Prints one JSON line: time from before the
 * autoloader to after terminate(), peak memory, included files, loaded providers.
 */

$start = hrtime(true);

$appDir = $argv[1] ?? exit("Usage: php probe.php <laravel-app-dir>\n");

// Laravel reads this once to decide runningInConsole(): boot the web path, not the console one.
$_SERVER['APP_RUNNING_IN_CONSOLE'] = 'false';
chdir($appDir.'/public');

require $appDir.'/vendor/autoload.php';

$app = require $appDir.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$request = Request::create('/');
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

$ms = (hrtime(true) - $start) / 1e6;

echo json_encode([
    'ms' => $ms,
    'status' => $response->getStatusCode(),
    'memory' => memory_get_peak_usage(true),
    'files' => count(get_included_files()),
    'providers' => count($app->getLoadedProviders()),
    'opcache' => function_exists('opcache_get_status') && (opcache_get_status(false)['opcache_enabled'] ?? false),
]), "\n";
