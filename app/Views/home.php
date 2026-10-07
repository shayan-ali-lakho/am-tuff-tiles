<?php /** @var array<int, array{name: string, text: string}> $categories */ ?>
<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <p class="eyebrow">Building products supplier</p>
            <h1>Tuff tiles, doors, gates and roofing, all in one place.</h1>
            <p class="hero-lead">
                AM Tuff Tiles supplies tuff tiles, doors, garden products, metal gates, roof ceilings and more
                for homes, shops and building projects.
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= e(url('/about')) ?>">About us</a>
                <?php if (config('shop.email')): ?>
                    <a class="btn btn-ghost" href="mailto:<?= e(config('shop.email')) ?>">Email us</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>What we offer</h2>
            <p>Our main product ranges. More products are added regularly.</p>
        </div>

        <ul class="card-grid">
            <?php foreach ($categories as $category): ?>
                <li class="card">
                    <h3><?= e($category['name']) ?></h3>
                    <p><?= e($category['text']) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
