<?php
/**
 * @var list<array<string, mixed>> $categories
 * @var list<array<string, mixed>> $featured
 */
?>
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
                <a class="btn btn-primary" href="<?= e(url('/shop')) ?>">Shop now</a>
                <a class="btn btn-ghost" href="<?= e(url('/about')) ?>">About us</a>
            </div>
        </div>
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
    <section class="section section-alt">
        <div class="container">
            <div class="section-head section-head-row">
                <div>
                    <h2>Featured products</h2>
                    <p>A selection from our shop.</p>
                </div>
                <a class="btn btn-secondary btn-sm" href="<?= e(url('/shop')) ?>">View all products</a>
            </div>

            <ul class="product-grid">
                <?php foreach ($featured as $product): ?>
                    <?= partial('shop/_card', ['product' => $product]) ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>
