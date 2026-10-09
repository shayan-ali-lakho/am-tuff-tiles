<?php
/**
 * @var list<array<string, mixed>> $categories
 * @var list<array<string, mixed>> $featured
 * @var list<array{label: string, href: string, photo: ?string, fallback: ?string}> $hero
 */
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-inner">
            <p class="eyebrow">Building products supplier</p>
            <h1>Tuff tiles, doors, gates and roofing, all in one place.</h1>
            <p class="hero-lead">
                AM Tuff Tiles supplies tuff tiles, doors, garden products, metal gates, roof ceilings and more
                for homes, shops and building projects.
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= e(url('/shop')) ?>">Shop now</a>
                <a class="btn btn-ghost" href="<?= e(url('/about')) ?>">About us</a>
            </div>
        </div>

        <ul class="hero-circles" aria-label="Product ranges">
            <?php foreach ($hero as $i => $circle): ?>
                <li class="hero-circle hero-circle-<?= e($i + 1) ?>">
                    <a href="<?= e(url($circle['href'])) ?>">
                        <?php if ($circle['photo'] !== null): ?>
                            <img src="<?= e(upload_url($circle['photo'], true)) ?>" alt="" width="320" height="320" loading="eager">
                        <?php elseif ($circle['fallback'] !== null): ?>
                            <img src="<?= e(asset($circle['fallback'])) ?>" alt="" width="320" height="320" loading="eager">
                        <?php endif; ?>
                        <span class="hero-circle-label"><?= e($circle['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>What we offer</h2>
            <p>Browse our main product ranges. More products are added regularly.</p>
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

<?php if ($featured !== []): ?>
    <section class="section section-alt section-featured">
        <div class="container">
            <div class="section-head section-head-row">
                <div>
                    <h2>Featured products</h2>
                    <p>A selection from our shop.</p>
                </div>
                <a class="btn btn-secondary btn-sm" href="<?= e(url('/shop')) ?>">View all products</a>
            </div>

            <div class="carousel" data-carousel>
                <button class="carousel-btn carousel-prev" type="button" data-carousel-prev aria-label="Previous products" hidden>
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
                </button>
                <ul class="product-grid carousel-track" data-carousel-track>
                    <?php foreach ($featured as $product): ?>
                        <?= partial('shop/_card', ['product' => $product]) ?>
                    <?php endforeach; ?>
                </ul>
                <button class="carousel-btn carousel-next" type="button" data-carousel-next aria-label="Next products" hidden>
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </section>
<?php endif; ?>

<?= partial('partials/map') ?>
