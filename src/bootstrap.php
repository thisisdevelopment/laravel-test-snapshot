<?php

use Illuminate\Contracts\Console\Kernel;
use ThisIsDevelopment\LaravelTestSnapshot\TestSnapshotCommand;

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

$app = require __DIR__ . '/../../../../bootstrap/app.php';
$console = tap($app->make(Kernel::class))->bootstrap();
foreach ($commands as $command) {
    $console->call($command);
}

unset($app, $console, $commands);
