<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$app = require BASE_PATH . '/config/app.php';
date_default_timezone_set($app['timezone']);

if ($app['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

session_set_cookie_params([
    'lifetime' => $app['session_lifetime'],
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once BASE_PATH . '/app/Helpers/functions.php';

$router = require BASE_PATH . '/config/routes.php';
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
