<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| cPanel Shared Hosting - Path Configuration
|--------------------------------------------------------------------------
| The app folder (Stock) sits one level above public_html.
| Structure:
|   /home/username/Stock/          <- Laravel app
|   /home/username/public_html/    <- Web root (contents of public/ go here)
|
| So from public_html/index.php, the app is at ../Stock
|--------------------------------------------------------------------------
*/
$appPath = realpath(__DIR__ . '/../Stock');

// Fallback: if running locally (XAMPP), app is one level up
if (!$appPath || !file_exists($appPath . '/vendor/autoload.php')) {
    $appPath = realpath(__DIR__ . '/..');
}

if (file_exists($maintenance = $appPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath . '/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appPath . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
