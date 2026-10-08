<?php
/** @var list<array<string, mixed>> $categories */
$siteName = (string) config('app.name');
$phone    = (string) config('shop.phone');
$email    = (string) config('shop.email');
$digits   = preg_replace('/\D+/', '', (string) config('shop.whatsapp'));
$facebook = social_url('facebook');
$instagram = social_url('instagram');
?>
<section class="page-head">
    <div class="container">
        <h1>About <?= e($siteName) ?></h1>
        <p class="page-head-sub">Quality building products for homes, shops and projects.</p>
    </div>
</section>

<section class="section">
    <div class="container about-intro">
        <div class="about-text">
            <p class="eyebrow eyebrow-dark">Who we are</p>
            <h2>Everything for your floors, doors, gates and roofs, in one place.</h2>
            <p>
                <?= e($siteName) ?> supplies tuff tiles, doors, garden products, metal gates, roof ceilings
                and more for homes, shops and building projects. Whether you are laying a new driveway,
                fitting a gate or finishing a ceiling, you can find what you need here and order it online.
            </p>
            <p>
                We keep ordering simple. Browse the shop, see clear prices in PKR, add what you need to your cart
                and pay in cash when your order is delivered.
            </p>
            <p class="about-actions">
                <a class="btn btn-primary" href="<?= e(url('/shop')) ?>">Visit the shop</a>
                <?php if ($digits !== ''): ?>
                    <a class="btn btn-secondary" href="https://wa.me/<?= e($digits) ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
                <?php endif; ?>
            </p>
        </div>

        <div class="about-media">
            <img src="<?= e(asset('img/about-tiles.jpg')) ?>" alt="Grey interlocking tuff tile pavers laid in a herringbone pattern" width="564" height="563" loading="lazy">
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <h2>What we offer</h2>
            <p>Our main product ranges. Tap one to see what is in stock.</p>
        </div>

        <ul class="card-grid">
            <?php foreach ($categories as $category): ?>
                <li class="card card-link-wrap">
                    <h3><a class="card-stretched" href="<?= e(url('/shop?category=' . rawurlencode((string) $category['slug']))) ?>"><?= e($category['name']) ?></a></h3>
                    <?php if (!empty($category['description'])): ?>
                        <p><?= e($category['description']) ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Why order from us</h2>
        </div>

        <ul class="about-points">
            <li>
                <h3>Clear prices</h3>
                <p>Every product shows its price in PKR, with size and material so you know what you are buying.</p>
            </li>
            <li>
                <h3>Pay on delivery</h3>
                <p>No online payment needed. You pay in cash when your order arrives.</p>
            </li>
            <li>
                <h3>Easy ordering</h3>
                <p>Pick your products, enter your delivery address and we call you to confirm your order.</p>
            </li>
            <li>
                <h3>Help when you need it</h3>
                <p>Not sure which product fits? Call or message us and we will help you choose.</p>
            </li>
        </ul>
    </div>
</section>

<section class="section section-alt">
    <div class="container about-cta">
        <div>
            <h2>Have a question or a big order?</h2>
            <p>Talk to us directly and we will help you with products and quantities.</p>
        </div>
        <p class="about-actions">
            <?php if ($phone !== ''): ?>
                <a class="btn btn-primary" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= icon('phone') ?> Call <?= e($phone) ?></a>
            <?php endif; ?>
            <?php if ($email !== ''): ?>
                <a class="btn btn-secondary" href="mailto:<?= e($email) ?>"><?= icon('mail') ?> Email us</a>
            <?php endif; ?>
            <?php if ($facebook !== ''): ?>
                <a class="btn btn-secondary" href="<?= e($facebook) ?>" target="_blank" rel="noopener noreferrer"><?= icon('facebook') ?> Facebook</a>
            <?php endif; ?>
            <?php if ($instagram !== ''): ?>
                <a class="btn btn-secondary" href="<?= e($instagram) ?>" target="_blank" rel="noopener noreferrer"><?= icon('instagram') ?> Instagram</a>
            <?php endif; ?>
        </p>
    </div>
</section>
