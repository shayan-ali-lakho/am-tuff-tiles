<?php
/** @var array<string, mixed> $product */
$href  = url('/product/' . $product['slug']);
$stock = (int) $product['stock_qty'];
?>
<li class="product-card">
    <a class="product-card-media" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true">
        <?php if (!empty($product['image_path'])): ?>
            <img src="<?= e(upload_url((string) $product['image_path'], true)) ?>" alt="" loading="lazy" width="640" height="480">
        <?php else: ?>
            <span class="product-card-empty">No photo yet</span>
        <?php endif; ?>
    </a>
    <div class="product-card-body">
        <p class="product-card-cat"><?= e($product['category_name']) ?></p>
        <h3 class="product-card-title"><a href="<?= e($href) ?>"><?= e($product['name']) ?></a></h3>
        <?php if (!empty($product['short_description'])): ?>
            <p class="product-card-desc"><?= e($product['short_description']) ?></p>
        <?php endif; ?>
        <p class="product-card-foot">
            <span class="product-price"><?= e(money((int) $product['price_paisa'])) ?></span>
            <?php if ($stock === 0): ?>
                <span class="badge badge-warn">Out of stock</span>
            <?php endif; ?>
        </p>
    </div>
</li>
