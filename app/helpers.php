<?php

declare(strict_types=1);

/**
 * Global helper functions used by controllers and views.
 */

/** Escape a value for safe output in HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Read a config value with dot notation, e.g. config('shop.email'). */
function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    $config ??= require BASE_PATH . '/config/config.php';

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

/** Root-relative URL for a page, e.g. url('/about'). */
function url(string $path = '/'): string
{
    return '/' . ltrim($path, '/');
}

/** URL for a file inside public/assets, e.g. asset('css/style.css'). */
function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';

    return '/assets/' . ltrim($path, '/') . $version;
}

/** Path of the current request without query string, e.g. "/about". */
function current_path(): string
{
    $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

    return '/' . trim($path, '/');
}

/** True when the current page is $path (or sits below it, unless $path is "/"). */
function is_active(string $path): bool
{
    $current = current_path();
    $path = '/' . trim($path, '/');

    if ($path === '/') {
        return $current === '/';
    }

    return $current === $path || str_starts_with($current, $path . '/');
}

/** Format a price stored in paisa as PKR, e.g. 125000 -> "PKR 1,250". */
function money(int $paisa): string
{
    $decimals = $paisa % 100 === 0 ? 0 : 2;

    return config('shop.currency', 'PKR') . ' ' . number_format($paisa / 100, $decimals);
}

/** Redirect and stop. */
function redirect(string $path, int $status = 302): never
{
    header('Location: ' . url($path), true, $status);
    exit;
}

/**
 * Render app/Views/{name}.php and wrap it in a layout.
 * Pass $layout = null to get the bare view.
 */
function view(string $name, array $data = [], ?string $layout = 'layouts/main'): string
{
    $render = static function (string $file, array $vars): string {
        extract($vars, EXTR_SKIP);
        ob_start();
        require $file;

        return (string) ob_get_clean();
    };

    $content = $render(BASE_PATH . '/app/Views/' . $name . '.php', $data);

    if ($layout === null) {
        return $content;
    }

    return $render(BASE_PATH . '/app/Views/' . $layout . '.php', $data + ['content' => $content]);
}
