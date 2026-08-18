<?php

// Vercel Functions expose only /tmp as writable storage. Laravel's compiled
// views are therefore redirected there; persistent data must use external
// database and object-storage services configured through environment vars.
$tmp = '/tmp/hummingbed-job-board';
foreach (['views', 'cache', 'sessions'] as $directory) {
    if (! is_dir($tmp.'/'.$directory)) mkdir($tmp.'/'.$directory, 0755, true);
}

$_ENV['VIEW_COMPILED_PATH'] = $_SERVER['VIEW_COMPILED_PATH'] = $tmp.'/views';
$_ENV['APP_SERVICES_CACHE'] = $_SERVER['APP_SERVICES_CACHE'] = $tmp.'/cache/services.php';
$_ENV['APP_PACKAGES_CACHE'] = $_SERVER['APP_PACKAGES_CACHE'] = $tmp.'/cache/packages.php';
$_ENV['APP_CONFIG_CACHE'] = $_SERVER['APP_CONFIG_CACHE'] = $tmp.'/cache/config.php';
$_ENV['APP_ROUTES_CACHE'] = $_SERVER['APP_ROUTES_CACHE'] = $tmp.'/cache/routes.php';
$_ENV['APP_EVENTS_CACHE'] = $_SERVER['APP_EVENTS_CACHE'] = $tmp.'/cache/events.php';

require __DIR__.'/../public/index.php';
