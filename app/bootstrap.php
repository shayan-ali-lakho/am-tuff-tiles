<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Autoload App\Foo\Bar from app/Foo/Bar.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/helpers.php';

App\Core\Env::load(BASE_PATH . '/.env');

date_default_timezone_set((string) config('app.timezone'));

// Errors: shown only when APP_DEBUG=true, always written to storage/logs/app.log
$debug = (bool) config('app.debug');
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/app.log');

set_exception_handler(static function (Throwable $e) use ($debug): void {
    error_log((string) $e);
    http_response_code(500);

    echo $debug
        ? '<pre>' . e((string) $e) . '</pre>'
        : view('errors/500', ['title' => 'Something went wrong']);
});

// Sessions: HttpOnly, SameSite=Lax, Secure when served over HTTPS
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
