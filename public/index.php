<?php

declare(strict_types=1);

// Local dev only: PHP's built-in server hands every request to this script,
// so let it serve real files (CSS, JS, images) itself. Apache does this on Hostinger.
if (PHP_SAPI === 'cli-server') {
    $requested = realpath(__DIR__ . (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

    if ($requested !== false && is_file($requested) && str_starts_with($requested, __DIR__ . DIRECTORY_SEPARATOR)
        && basename($requested) !== 'index.php') {
        return false;
    }
}

// Front controller: every page request enters here.
require dirname(__DIR__) . '/app/bootstrap.php';

$router = new App\Core\Router();
require BASE_PATH . '/app/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
