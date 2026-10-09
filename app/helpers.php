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
    seo_noindex(false);
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

/** EasyPaisa account details from the settings; the option is only offered at checkout when a number is set. */
function easypaisa(): array
{
    $number = trim((string) config('shop.easypaisa_number'));

    return [
        'enabled' => $number !== '',
        'number'  => $number,
        'name'    => trim((string) config('shop.easypaisa_name')),
    ];
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

/**
 * Queue a pop-up message (modal) for the NEXT page.
 *
 * $type    success | error | warning | info
 * $actions buttons shown under the message, e.g. [['label' => 'View order', 'href' => '/order/AM-1', 'primary' => true]].
 *          An action without "href" simply closes the pop-up. With no actions a success pop-up closes by itself.
 */
function notify(string $type, string $title, string $message = '', array $actions = []): void
{
    $type = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
    flash('modal', ['type' => $type, 'title' => $title, 'message' => $message, 'actions' => $actions]);
}

/**
 * The pop-up to show on this page, or null. An explicit notify() wins; otherwise the plain flash messages
 * (success / warning / error / info) are turned into one. Any other messages are listed underneath as notes.
 *
 * @return array{type: string, title: string, message: string, notes: list<string>, actions: list<array<string, mixed>>}|null
 */
function modal_data(): ?array
{
    $titles = ['success' => 'Done', 'error' => 'Something went wrong', 'warning' => 'Please note', 'info' => 'Please note'];
    $found = [];

    foreach (['error', 'warning', 'success', 'info'] as $type) {
        $text = flash_get($type);

        if (is_string($text) && $text !== '') {
            $found[$type] = $text;
        }
    }

    $explicit = flash_get('modal');

    if (is_array($explicit) && isset($explicit['type'], $explicit['title'])) {
        $type = (string) $explicit['type'];
        unset($found[$type]);

        return [
            'type'    => $type,
            'title'   => (string) $explicit['title'],
            'message' => (string) ($explicit['message'] ?? ''),
            'notes'   => array_values($found),
            'actions' => is_array($explicit['actions'] ?? null) ? array_values($explicit['actions']) : [],
        ];
    }

    if ($found === []) {
        return null;
    }

    $type = (string) array_key_first($found);
    $message = $found[$type];
    unset($found[$type]);

    return ['type' => $type, 'title' => $titles[$type], 'message' => $message, 'notes' => array_values($found), 'actions' => []];
}

/** Round icon for a pop-up. */
function modal_icon(string $type): string
{
    $paths = [
        'success' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'error'   => '<path d="M6 6l12 12M18 6L6 18"/>',
        'warning' => '<path d="M12 7v6"/><path d="M12 17.2v.01"/>',
        'info'    => '<path d="M12 11v6"/><path d="M12 7.2v.01"/>',
    ];

    return '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$type] ?? $paths['info']) . '</svg>';
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


/** Small inline icons (no external files). Returns SVG markup; the icon is decorative. */
function icon(string $name): string
{
    $paths = [
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'whatsapp' => '<path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.3z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a4 4 0 0 1-1.8-1.8l.8-1-1-2z"/>',
        'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="0.8" fill="currentColor"/>',
        'pin'      => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    ];

    return '<svg class="icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . ($paths[$name] ?? '') . '</svg>';
}

/** URL of the shop logo if a file public/assets/img/logo.(svg|png|webp|jpg) exists, otherwise null. */
function logo_url(): ?string
{
    foreach (['svg', 'png', 'webp', 'jpg', 'jpeg'] as $ext) {
        $file = dirname(__DIR__) . '/public/assets/img/logo.' . $ext;

        if (is_file($file)) {
            return asset('img/logo.' . $ext) . '?v=' . filemtime($file);
        }
    }

    return null;
}


/** A social page address, only if it is a real http(s) link; otherwise empty so nothing is shown. */
function social_url(string $key): string
{
    $url = trim((string) config('shop.' . $key));

    return filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url) === 1 ? $url : '';
}


/**
 * Address for the Google Map frame, or '' when no location is set.
 * SHOP_MAP_EMBED (the src from Google Maps > Share > Embed a map) is used if present, otherwise SHOP_MAP_QUERY (an address
 * or place name) is turned into an embed address. Only Google Maps addresses are ever accepted.
 */
function map_embed_url(): string
{
    $embed = trim((string) config('shop.map_embed'));

    if ($embed !== '' && preg_match('#^https://www\.google\.com/maps/embed\?[^\s"\'<>]+$#', $embed) === 1) {
        return $embed;
    }

    $query = trim((string) config('shop.map_query'));

    return $query !== '' ? 'https://www.google.com/maps?q=' . rawurlencode(mb_substr($query, 0, 200)) . '&output=embed' : '';
}

/** "Open in Google Maps" address for the map section. */
function map_link_url(): string
{
    $link = trim((string) config('shop.map_link'));

    if ($link !== '' && preg_match('#^https://(www\.google\.com/maps|maps\.app\.goo\.gl|goo\.gl/maps|g\.page)[^\s"\'<>]*$#', $link) === 1) {
        return $link;
    }

    $query = trim((string) config('shop.map_query'));

    return $query !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(mb_substr($query, 0, 200)) : '';
}


// ----------------------------------------------------------------------
// SEO
// ----------------------------------------------------------------------

/** Public address of the site, e.g. https://amtufftiles.com (works even if APP_URL is typed slightly wrong). */
function site_url(): string
{
    return \App\Core\Mailer::baseUrl();
}

/**
 * Collects search-engine details for the current page. Call seo([...]) before rendering to set
 * canonical, robots, image, type and jsonld (a list of structured-data arrays); call seo() to read them.
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function seo(array $data = []): array
{
    static $store = ['jsonld' => []];

    if (isset($data['jsonld'])) {
        $store['jsonld'] = array_merge($store['jsonld'], (array) $data['jsonld']);
        unset($data['jsonld']);
    }

    $store = array_merge($store, $data);

    return $store;
}

/** Tell search engines not to list this page (also sent as a header so it works for every kind of page). */
function seo_noindex(bool $follow = true): void
{
    $value = $follow ? 'noindex, follow' : 'noindex, nofollow';
    seo(['robots' => $value]);

    if (!headers_sent()) {
        header('X-Robots-Tag: ' . $value);
    }
}

/** Pages that must never appear in search results (accounts, cart, checkout, admin). */
function seo_is_private_path(string $path): bool
{
    foreach (['/admin', '/login', '/register', '/forgot-password', '/reset-password', '/cart', '/checkout', '/order', '/orders'] as $prefix) {
        if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
            return true;
        }
    }

    return false;
}

/** Structured data describing the shop itself (shown by Google as a business). */
function business_schema(): array
{
    $site = site_url();
    $data = [
        '@context'           => 'https://schema.org',
        '@type'              => 'Store',
        '@id'                => $site . '/#business',
        'name'               => (string) config('app.name'),
        'alternateName'      => 'Abdul Manan Tiles',
        'url'                => $site . '/',
        'logo'               => $site . asset_path('img/logo.png'),
        'image'              => $site . asset_path('img/og-default.jpg'),
        'description'        => 'Tuff tiles, doors, garden products, metal gates, roof ceilings and more. Prices in PKR, ' . (easypaisa()['enabled'] ? 'cash on delivery or EasyPaisa' : 'cash on delivery') . '.',
        'currenciesAccepted' => 'PKR',
        'paymentAccepted'    => easypaisa()['enabled'] ? 'Cash on delivery, EasyPaisa' : 'Cash on delivery',
        'address'            => ['@type' => 'PostalAddress', 'addressLocality' => 'Karachi', 'addressCountry' => 'PK'],
        'geo'                => ['@type' => 'GeoCoordinates', 'latitude' => 24.917186842983497, 'longitude' => 66.96014607642219],
    ];

    if ((string) config('shop.phone') !== '') {
        $data['telephone'] = '+92' . ltrim(preg_replace('/\D+/', '', (string) config('shop.phone')) ?? '', '0');
    }
    if ((string) config('shop.email') !== '') {
        $data['email'] = (string) config('shop.email');
    }

    $same = array_values(array_filter([social_url('facebook'), social_url('instagram')]));
    if ($same !== []) {
        $data['sameAs'] = $same;
    }

    return $data;
}

/** Asset path without the version suffix, for use inside absolute URLs. */
function asset_path(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

/** Breadcrumb structured data from [name => path] pairs. */
function breadcrumb_schema(array $crumbs): array
{
    $items = [];
    $i = 1;

    foreach ($crumbs as $name => $path) {
        $items[] = ['@type' => 'ListItem', 'position' => $i++, 'name' => (string) $name, 'item' => site_url() . $path];
    }

    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}
