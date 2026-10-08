<?php
/**
 * @var array<string, mixed> $product
 * @var list<array<string, mixed>> $images
 * @var list<array<string, mixed>> $related
 */
$stock = (int) $product['stock_qty'];
$name  = (string) $product['name'];
$email = (string) config('shop.email');
$phone = (string) config('shop.phone');
$whatsapp = preg_replace('/\D+/', '', (string) config('shop.whatsapp'));
?>
<section class="section product-page">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('/')) ?>">Home</a>
            <span aria-hidden="true">/</span>
            <a href="<?= e(url('/shop')) ?>">Shop</a>
            <span aria-hidden="true">/</span>
            <a href="<?= e(url('/shop?category=' . rawurlencode((string) $product['category_slug']))) ?>"><?= e($product['category_name']) ?></a>
        </nav>

        <div class="product-layout">
            <div class="gallery" data-gallery>
                <?php if ($images === []): ?>
                    <div class="gallery-main gallery-empty">No photo yet</div>
                <?php else: ?>
                    <a class="gallery-main" href="<?= e(upload_url((string) $images[0]['file_path'])) ?>" target="_blank" rel="noopener">
                        <img src="<?= e(upload_url((string) $images[0]['file_path'])) ?>" alt="<?= e($name) ?>" data-gallery-main width="1200" height="900">
                    </a>

                    <?php if (count($images) > 1): ?>
                        <ul class="gallery-thumbs">
                            <?php foreach ($images as $i => $image): ?>
                                <li>
                                    <a href="<?= e(upload_url((string) $image['file_path'])) ?>"
                                       data-gallery-thumb data-full="<?= e(upload_url((string) $image['file_path'])) ?>"
                                       class="<?= $i === 0 ? 'is-active' : '' ?>"
                                       aria-label="Show photo <?= e($i + 1) ?> of <?= e(count($images)) ?>">
                                        <img src="<?= e(upload_url((string) $image['file_path'], true)) ?>" alt="" loading="lazy" width="120" height="90">
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="product-info">
                <p class="eyebrow eyebrow-dark"><?= e($product['category_name']) ?></p>
                <h1><?= e($name) ?></h1>

                <p class="product-price product-price-lg"><?= e(money((int) $product['price_paisa'])) ?></p>

                <p class="stock-line">
                    <?php if ($stock === 0): ?>
                        <span class="badge badge-warn">Out of stock</span>
                    <?php elseif ($stock <= 5): ?>
                        <span class="badge badge-warn">Only <?= e($stock) ?> left</span>
                    <?php else: ?>
                        <span class="badge badge-ok">In stock</span>
                    <?php endif; ?>
                </p>

                <?php if (!empty($product['short_description'])): ?>
                    <p class="product-lead"><?= e($product['short_description']) ?></p>
                <?php endif; ?>

                <dl class="specs">
                    <div><dt>Category</dt><dd><?= e($product['category_name']) ?></dd></div>
                    <?php if (!empty($product['size'])): ?>
                        <div><dt>Size</dt><dd><?= e($product['size']) ?></dd></div>
                    <?php endif; ?>
                    <?php if (!empty($product['material'])): ?>
                        <div><dt>Material</dt><dd><?= e($product['material']) ?></dd></div>
                    <?php endif; ?>
                </dl>

                <!-- Temporary until the cart and checkout are built: ordering by contacting the shop. -->
                <div class="order-note">
                    <p><strong>Online ordering is opening soon.</strong> To order this product now, contact us:</p>
                    <p class="order-note-links">
                        <?php if ($whatsapp !== ''): ?>
                            <a class="btn btn-primary btn-sm" href="https://wa.me/<?= e($whatsapp) ?>?text=<?= e(rawurlencode('Hello, I am interested in: ' . $name)) ?>">WhatsApp</a>
                        <?php endif; ?>
                        <?php if ($phone !== ''): ?>
                            <a class="btn btn-secondary btn-sm" href="tel:<?= e($phone) ?>">Call <?= e($phone) ?></a>
                        <?php endif; ?>
                        <?php if ($email !== ''): ?>
                            <a class="btn btn-secondary btn-sm" href="mailto:<?= e($email) ?>?subject=<?= e(rawurlencode('Enquiry: ' . $name)) ?>">Email us</a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if (!empty($product['description'])): ?>
            <div class="product-description">
                <h2>Description</h2>
                <p><?= nl2br(e($product['description'])) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($related !== []): ?>
            <div class="related">
                <h2>More in <?= e($product['category_name']) ?></h2>
                <ul class="product-grid">
                    <?php foreach ($related as $item): ?>
                        <?= partial('shop/_card', ['product' => $item]) ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>
