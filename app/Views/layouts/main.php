<?php
/** @var string $content */
$siteName = (string) config('app.name');
$pageTitle = ($title ?? '') !== '' ? $title . ' | ' . $siteName : 'Tuff Tiles, Doors & Metal Gates in Karachi | ' . $siteName;
$pageDescription = $description ?? 'AM Tuff Tiles supplies tuff tiles, doors, garden products, metal gates and roof ceilings. Browse prices in PKR and order online with cash on delivery.';
$seo = seo();
$canonical = (string) ($seo['canonical'] ?? (site_url() . (current_path() === '/' ? '/' : current_path())));
$ogImage = (string) ($seo['image'] ?? (site_url() . asset_path('img/og-default.jpg')));
$ogType = (string) ($seo['type'] ?? 'website');
$currentUser = \App\Core\Auth::user();
$firstName = $currentUser !== null ? (explode(' ', trim((string) $currentUser['full_name']))[0] ?? '') : '';
$cartCount = \App\Models\Cart::count();
$logo = logo_url();
$phone = (string) config('shop.phone');
$whatsappDigits = preg_replace('/\D+/', '', (string) config('shop.whatsapp'));
$facebook = social_url('facebook');
$instagram = social_url('instagram');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#1F2933">
    <?php if (!empty($seo['robots'])): ?><meta name="robots" content="<?= e($seo['robots']) ?>">
    <?php else: ?><meta name="robots" content="index, follow, max-image-preview:large">
    <?php endif; ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:type" content="<?= e($ogType) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:locale" content="en_PK">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">
    <?php foreach ($seo['jsonld'] ?? [] as $schema): ?>
    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <?php endforeach; ?>
    <link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
    <script>document.documentElement.className += ' js';</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Poppins:wght@600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= e(url('/')) ?>">
                <?php if ($logo !== null): ?>
                    <img class="brand-logo" src="<?= e($logo) ?>" alt="" width="44" height="44">
                    <span class="brand-name"><?= e($siteName) ?></span>
                <?php else: ?>
                    <span class="brand-mark" aria-hidden="true">AM</span>
                    <span class="brand-name"><?= e($siteName) ?></span>
                <?php endif; ?>
            </a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
                <span class="nav-toggle-label">Menu</span>
            </button>

            <nav id="site-nav" class="site-nav" aria-label="Main">
                <a href="<?= e(url('/')) ?>"<?= is_active('/') ? ' class="is-active" aria-current="page"' : '' ?>>Home</a>
                <a href="<?= e(url('/shop')) ?>"<?= is_active('/shop') || is_active('/product') ? ' class="is-active" aria-current="page"' : '' ?>>Shop</a>
                <a href="<?= e(url('/cart')) ?>"<?= is_active('/cart') || is_active('/checkout') ? ' class="is-active" aria-current="page"' : '' ?>>Cart<?php if ($cartCount > 0): ?> <span class="cart-count" aria-label="<?= e($cartCount) ?> items"><?= e($cartCount) ?></span><?php endif; ?></a>
                <a href="<?= e(url('/about')) ?>"<?= is_active('/about') ? ' class="is-active" aria-current="page"' : '' ?>>About</a>

                <?php if ($currentUser !== null): ?>
                    <?php if (($currentUser['portal_role'] ?? '') === 'admin'): ?>
                        <a href="<?= e(url('/admin')) ?>"<?= is_active('/admin') ? ' class="is-active" aria-current="page"' : '' ?>>Admin</a>
                    <?php endif; ?>
                    <a href="<?= e(url('/orders')) ?>"<?= is_active('/orders') || is_active('/order') ? ' class="is-active" aria-current="page"' : '' ?>>My orders</a>
                    <span class="nav-user">Hi, <?= e($firstName) ?></span>
                    <form method="post" action="<?= e(url('/logout')) ?>" class="nav-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="nav-link-btn">Log out</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('/login')) ?>"<?= is_active('/login') ? ' class="is-active" aria-current="page"' : '' ?>>Log in</a>
                    <a class="nav-cta" href="<?= e(url('/register')) ?>">Create account</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main id="main">
        <?php foreach (['success' => 'alert-success', 'warning' => 'alert-warning', 'error' => 'alert-error', 'info' => 'alert-info'] as $type => $alertClass): ?>
            <?php $flashMessage = flash_get($type); ?>
            <?php if (is_string($flashMessage) && $flashMessage !== ''): ?>
                <div class="container flash-wrap">
                    <div class="alert <?= e($alertClass) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>"><?= e($flashMessage) ?></div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-col">
                <p class="footer-brand"><?= e($siteName) ?></p>
                <p class="footer-muted">Tuff tiles, doors, gardens, metal gates, roof ceilings and more.</p>
            </div>

            <nav class="footer-col" aria-label="Footer">
                <p class="footer-heading">Quick links</p>
                <ul class="footer-links">
                    <li><a href="<?= e(url('/')) ?>">Home</a></li>
                    <li><a href="<?= e(url('/shop')) ?>">Shop</a></li>
                    <li><a href="<?= e(url('/cart')) ?>">Cart</a></li>
                    <li><a href="<?= e(url('/about')) ?>">About</a></li>
                    <?php if ($currentUser !== null): ?>
                        <li><a href="<?= e(url('/orders')) ?>">My orders</a></li>
                    <?php else: ?>
                        <li><a href="<?= e(url('/login')) ?>">Log in</a></li>
                        <li><a href="<?= e(url('/register')) ?>">Create account</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="footer-col">
                <p class="footer-heading">Contact</p>
                <ul class="footer-contact">
                    <?php if ($phone !== ''): ?>
                        <li><?= icon('phone') ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></li>
                    <?php endif; ?>
                    <?php if ($whatsappDigits !== ''): ?>
                        <li><?= icon('whatsapp') ?><a href="https://wa.me/<?= e($whatsappDigits) ?>" target="_blank" rel="noopener">WhatsApp us</a></li>
                    <?php endif; ?>
                    <?php if (config('shop.email')): ?>
                        <li><?= icon('mail') ?><a href="mailto:<?= e(config('shop.email')) ?>"><?= e(config('shop.email')) ?></a></li>
                    <?php endif; ?>
                    <?php if ($facebook !== ''): ?>
                        <li><?= icon('facebook') ?><a href="<?= e($facebook) ?>" target="_blank" rel="noopener noreferrer">Facebook</a></li>
                    <?php endif; ?>
                    <?php if ($instagram !== ''): ?>
                        <li><?= icon('instagram') ?><a href="<?= e($instagram) ?>" target="_blank" rel="noopener noreferrer">Instagram</a></li>
                    <?php endif; ?>
                    <?php if (config('shop.address')): ?>
                        <li><?= icon('pin') ?><span><?= e(config('shop.address')) ?></span></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?= e(date('Y')) ?> <?= e($siteName) ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
