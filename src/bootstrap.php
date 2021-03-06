<?php

use Illuminate\Contracts\Console\Kernel;
use ThisIsDevelopment\LaravelTestSnapshot\Commands\TestSnapshotCommand;
use Illuminate\Console\Application as Artisan;

require_once __DIR__ . '/../../../autoload.php';

/*
|--------------------------------------------------------------------------
| Bootstrap the testing environment
|--------------------------------------------------------------------------
|
| You have the option to specify console commands that will execute before your
| test suite is run. Caching config, routes, & events may improve performance
| and bring your testing environment closer to production.
|
*/

if ($_SERVER['PHP_SELF'] === 'Standard input code') {
    // do not execute bootstrap commands for tests run with process isolation
    return;
}

$commands = [
    'cache:clear',
    'config:clear',
    'event:clear',
    'route:clear',
    'config:cache',
    'event:cache',
    'route:cache',
    TestSnapshotCommand::class
];

if ($_SERVER['DB_DATABASE'] !== ':memory:' && !file_exists($_SERVER['DB_DATABASE'])) {
    file_put_contents($_SERVER['DB_DATABASE'], '');
}

ini_set('memory_limit', '256M');

/** @var \Illuminate\Contracts\Foundation\Application $app */
$app = require __DIR__ . '/../../../../bootstrap/app.php';

/** @var Kernel $console */
$console = tap($app->make(Kernel::class))->bootstrap();

/*
 * Dingo interoparability; from the dingo wiki (https://github.com/dingo/api/wiki/Commands#apicache)
 * Important: Running route:cache by itself will prevent your API routes from being accessible.
 */
if ($app->bound(\Dingo\Api\Routing\Router::class)) {
    /*
     * Note: api:cache causes issues with mocks in tests, don't use route caching with dingo for now
     */
    $commands[] = 'route:clear';
}

Artisan::starting(function (Artisan $artisan) {
    $artisan->resolveCommands(TestSnapshotCommand::class);
});

foreach ($commands as $command) {
    $console->call($command);
}

unset($app, $console, $commands);

