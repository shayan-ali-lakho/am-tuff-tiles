<?php
/** @var string $content */
$siteName = (string) config('app.name');
$pageTitle = ($title ?? '') !== '' ? $title . ' | ' . $siteName : $siteName . ' | Tuff Tiles, Doors, Gates & More';
$pageDescription = $description ?? 'AM Tuff Tiles supplies tuff tiles, doors, garden products, metal gates, roof ceilings and more.';
$currentUser = \App\Core\Auth::user();
$firstName = $currentUser !== null ? (explode(' ', trim((string) $currentUser['full_name']))[0] ?? '') : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#1F2933">

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
                <span class="brand-mark" aria-hidden="true">AM</span>
                <span class="brand-name"><?= e($siteName) ?></span>
            </a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
                <span class="nav-toggle-label">Menu</span>
            </button>

            <nav id="site-nav" class="site-nav" aria-label="Main">
                <a href="<?= e(url('/')) ?>"<?= is_active('/') ? ' class="is-active" aria-current="page"' : '' ?>>Home</a>
                <a href="<?= e(url('/about')) ?>"<?= is_active('/about') ? ' class="is-active" aria-current="page"' : '' ?>>About</a>

                <?php if ($currentUser !== null): ?>
                    <?php if (($currentUser['portal_role'] ?? '') === 'admin'): ?>
                        <a href="<?= e(url('/admin')) ?>"<?= is_active('/admin') ? ' class="is-active" aria-current="page"' : '' ?>>Admin</a>
                    <?php endif; ?>
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
            <div>
                <p class="footer-brand"><?= e($siteName) ?></p>
                <p class="footer-muted">Tuff tiles, doors, gardens, metal gates, roof ceilings and more.</p>
            </div>
            <div>
                <p class="footer-heading">Contact</p>
                <?php if (config('shop.email')): ?>
                    <p><a href="mailto:<?= e(config('shop.email')) ?>"><?= e(config('shop.email')) ?></a></p>
                <?php endif; ?>
                <?php if (config('shop.phone')): ?>
                    <p><a href="tel:<?= e(config('shop.phone')) ?>"><?= e(config('shop.phone')) ?></a></p>
                <?php endif; ?>
                <?php if (config('shop.address')): ?>
                    <p><?= e(config('shop.address')) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; <?= e(date('Y')) ?> <?= e($siteName) ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
