<?php
define('LARAVEL_START', microtime(true));

// Check if the dependencies are installed. If someone does a git clone without then
// installing the dependencies, we can tell them to do so. If they download a release,
// the dependencies should have already come with that download.
if(!file_exists(__DIR__.'/../vendor/autoload.php')) {
    require(__DIR__.'/../resources/setup/missing-dependencies.php');
    die();
}

require __DIR__.'/../vendor/autoload.php';

// Load the .env file if it exists
$dotenv = Dotenv\Dotenv::create(__DIR__.'/..');
if(file_exists(__DIR__.'/../.env')) {
  $dotenv->load();
}

// Check for environment variables and trigger the setup flow if it doesn't exist
if(!getenv('APP_NAME')) {
    $_ENV['APP_NAME'] = 'Meetable Installer';
    // Use a temporary fixed APP_KEY during installation
    $_ENV['APP_KEY'] = 'base64:v7ZDOfJbqzXdbbJ/3GYSAP+B4jm3rMlrWiNutsaQYEE=';
    // Use cookie driver for setup, will switch back to database when setup is complete
    $_ENV['SESSION_DRIVER'] = 'cookie';
    // Setting the MEETABLE_SETUP to true will trigger `routes/web.php` to
    // define the setup routes instead of app routes
    define('MEETABLE_SETUP', true);
}

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
