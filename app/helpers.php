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

/** Stop with the standard 403 or 404 page. */
function abort(int $status): never
{
    http_response_code($status);
    echo view('errors/' . $status, ['title' => $status === 403 ? 'Access denied' : 'Page not found']);
    exit;
}

/** Render a view without the page layout (for repeated blocks such as the admin menu). */
function partial(string $name, array $data = []): string
{
    return view($name, $data, null);
}

/** URL-friendly text: "Grey Tuff Tile 30x30" -> "grey-tuff-tile-30x30". May be empty for non-Latin names. */
function slugify(string $text): string
{
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (is_string($converted)) {
            $text = $converted;
        }
    }

    $text = strtolower($text);
    $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);

    return trim($text, '-');
}

/**
 * Turn what an admin typed ("1250", "1,250", "Rs 1250.50") into paisa (125000, 125050).
 * Returns null when it is not a valid amount.
 */
function parse_price(string $input): ?int
{
    $clean = preg_replace('/^(pkr|rs\.?|₨)\s*/i', '', trim($input));
    $clean = str_replace([',', ' '], '', (string) $clean);

    if (preg_match('/^(\d{1,9})(?:\.(\d{1,2}))?$/', $clean, $m) !== 1) {
        return null;
    }

    return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
}

/** Paisa -> text for a price input: 125000 -> "1250", 125050 -> "1250.50". */
function price_input(int $paisa): string
{
    return $paisa % 100 === 0 ? (string) intdiv($paisa, 100) : number_format($paisa / 100, 2, '.', '');
}

/** Public URL of a stored photo (path as saved in product_images.file_path). */
function upload_url(string $path, bool $thumb = false): string
{
    if ($thumb) {
        $path = preg_replace('/\.jpg$/', '_thumb.jpg', $path) ?? $path;
    }

    return '/uploads/' . ltrim($path, '/');
}

/** Hidden CSRF input for every POST form. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(\App\Core\Csrf::token()) . '">';
}

/**
 * One-time messages that survive a redirect (used for "Welcome back" notices and form errors).
 * flash() stores for the NEXT request; flash_get() reads what the previous request stored.
 */
function flash(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function flash_get(string $key, mixed $default = null): mixed
{
    return $GLOBALS['__flash'][$key] ?? $default;
}

/** Accept only a local path like "/shop" for post-login redirects (blocks open redirects). */
function safe_next(mixed $path, string $default = '/'): string
{
    $path = is_string($path) ? $path : '';

    if (
        $path === ''
        || $path[0] !== '/'
        || str_starts_with($path, '//')
        || str_contains($path, '\\')
        || preg_match('/[\x00-\x1f\x7f]/', $path) === 1
    ) {
        return $default;
    }

    return $path;
}

/** Inline error message under a form field. */
function field_error(array $errors, string $key): string
{
    return isset($errors[$key])
        ? '<p class="field-error" id="' . e($key) . '-error">' . e($errors[$key]) . '</p>'
        : '';
}

/** aria attributes that tie an input to its error message. */
function error_attrs(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="' . e($key) . '-error"' : '';
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
