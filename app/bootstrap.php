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

// Sessions (web requests only): HttpOnly, SameSite=Lax, Secure when served over HTTPS.
// Session files live in storage/sessions (private to this app) and expire after 8 hours without
// activity, so the shared host's shorter default cleanup cannot log shoppers out early.
$GLOBALS['__flash'] = [];

if (PHP_SAPI !== 'cli') {
    // Browser protections on every page the app serves
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; frame-src https://www.google.com; script-src 'self' 'unsafe-inline'; form-action 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'");
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000');
    }

    $sessionDir = BASE_PATH . '/storage/sessions';
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '28800');
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Messages stored by the previous request are available to this one only.
    $GLOBALS['__flash'] = is_array($_SESSION['_flash'] ?? null) ? $_SESSION['_flash'] : [];
    unset($_SESSION['_flash']);
}
