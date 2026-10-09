<?php
/** @var array{lines: list<array<string, mixed>>, subtotal: int, delivery: int, total: int, notices: list<string>} $cart */
?>
<section class="page-head">
    <div class="container"><h1>Your cart</h1></div>
</section>

<section class="section">
    <div class="container">
        <?php foreach ($cart['notices'] as $notice): ?>
            <div class="alert alert-warning" role="status"><?= e($notice) ?></div>
        <?php endforeach; ?>

        <?php if ($cart['lines'] === []): ?>
            <div class="empty-state">
                <p>Your cart is empty.</p>
                <a class="btn btn-primary" href="<?= e(url('/shop')) ?>">Browse the shop</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <ul class="cart-lines">
                    <?php foreach ($cart['lines'] as $line): ?>
                        <li class="cart-line">
                            <a class="cart-thumb" href="<?= e(url('/product/' . $line['slug'])) ?>" tabindex="-1" aria-hidden="true">
                                <?php if (!empty($line['image_path'])): ?>
                                    <img src="<?= e(upload_url((string) $line['image_path'], true)) ?>" alt="" width="96" height="72" loading="lazy">
                                <?php else: ?>
                                    <span class="cart-thumb-empty">No photo</span>
                                <?php endif; ?>
                            </a>

                            <div class="cart-info">
                                <a class="cart-name" href="<?= e(url('/product/' . $line['slug'])) ?>"><?= e($line['name']) ?></a>
                                <span class="cart-unit"><?= e(money((int) $line['price_paisa'])) ?> each</span>

                                <div class="cart-actions">
                                    <form method="post" action="<?= e(url('/cart/update')) ?>" class="cart-qty-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= e($line['id']) ?>">
                                        <label class="sr-only" for="qty-<?= e($line['id']) ?>">Quantity</label>
                                        <input id="qty-<?= e($line['id']) ?>" class="qty-input" type="number" name="qty" min="1" max="<?= e(min(999, (int) $line['stock_qty'])) ?>" value="<?= e($line['qty']) ?>" inputmode="numeric">
                                        <button class="btn btn-secondary btn-sm" type="submit">Update</button>
                                    </form>
                                    <form method="post" action="<?= e(url('/cart/remove')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= e($line['id']) ?>">
                                        <button class="link-btn" type="submit">Remove</button>
                                    </form>
                                </div>
                            </div>

                            <p class="cart-total"><?= e(money((int) $line['line_total'])) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <aside class="summary-card" aria-label="Order summary">
                    <h2>Summary</h2>
                    <dl class="summary-rows">
                        <div><dt>Subtotal</dt><dd><?= e(money($cart['subtotal'])) ?></dd></div>
                        <div><dt>Delivery</dt><dd><?= $cart['delivery'] > 0 ? e(money($cart['delivery'])) : 'Free' ?></dd></div>
                        <div class="summary-total"><dt>Total</dt><dd><?= e(money($cart['total'])) ?></dd></div>
                    </dl>
                    <p class="field-hint">No account needed. <?= easypaisa()['enabled'] ? 'Pay by cash on delivery or EasyPaisa.' : 'Payment is cash on delivery.' ?></p>
                    <a class="btn btn-primary btn-block" href="<?= e(url('/checkout')) ?>">Checkout</a>
                    <p class="summary-more"><a href="<?= e(url('/shop')) ?>">Continue shopping</a></p>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>
