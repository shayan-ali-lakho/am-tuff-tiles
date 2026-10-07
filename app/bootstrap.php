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
// Keep function arguments (e.g. database passwords) out of exception traces and logs
ini_set('zend.exception_ignore_args', '1');

set_exception_handler(static function (Throwable $e) use ($debug): void {
    error_log((string) $e);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }

    http_response_code(500);

    echo $debug
        ? '<pre>' . e((string) $e) . '</pre>'
        : view('errors/500', ['title' => 'Something went wrong']);
});

// Sessions (web requests only): HttpOnly, SameSite=Lax, Secure when served over HTTPS
if (PHP_SAPI !== 'cli') {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
