<?php
define('LARAVEL_START', microtime(true));

// Check if the dependencies are installed. If someone does a git clone without then
// installing the dependencies, we can tell them to do so. If they download a release,
// the dependencies should have already come with that download.
if(!file_exists(__DIR__.'/../vendor/autoload.php')) {
    require_once(__DIR__.'/../resources/setup/missing-dependencies.php');
    die();
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
