<?php
/**
 * @var array{lines: list<array<string, mixed>>, subtotal: int, delivery: int, total: int, notices: list<string>} $cart
 * @var array<string, string> $errors
 * @var array<string, string> $old
 */
$f = static fn (string $k): string => e($old[$k] ?? '');
?>
<section class="page-head">
    <div class="container"><h1>Checkout</h1></div>
</section>

<section class="section">
    <div class="container">
        <?php if ($errors !== []): ?>
            <div class="alert alert-error" role="alert">Please fix the highlighted fields and try again.</div>
        <?php endif; ?>

        <div class="cart-layout">
            <form method="post" action="<?= e(url('/checkout')) ?>" class="form checkout-form" novalidate>
                <?= csrf_field() ?>
                <h2>Delivery details</h2>

                <div class="field">
                    <label for="customer_name">Full name</label>
                    <input id="customer_name" name="customer_name" type="text" autocomplete="name" required maxlength="120" value="<?= $f('customer_name') ?>"<?= error_attrs($errors, 'customer_name') ?>>
                    <?= field_error($errors, 'customer_name') ?>
                </div>

                <div class="field">
                    <label for="customer_phone">Phone</label>
                    <input id="customer_phone" name="customer_phone" type="tel" autocomplete="tel" required maxlength="20" value="<?= $f('customer_phone') ?>"<?= error_attrs($errors, 'customer_phone') ?>>
                    <p class="field-hint">We call this number to confirm your order.</p>
                    <?= field_error($errors, 'customer_phone') ?>
                </div>

                <div class="field">
                    <label for="customer_email">Email</label>
                    <input id="customer_email" name="customer_email" type="email" autocomplete="email" required maxlength="190" value="<?= $f('customer_email') ?>"<?= error_attrs($errors, 'customer_email') ?>>
                    <?= field_error($errors, 'customer_email') ?>
                </div>

                <div class="field">
                    <label for="shipping_address">Delivery address</label>
                    <input id="shipping_address" name="shipping_address" type="text" autocomplete="street-address" required maxlength="255" value="<?= $f('shipping_address') ?>"<?= error_attrs($errors, 'shipping_address') ?>>
                    <?= field_error($errors, 'shipping_address') ?>
                </div>

                <div class="field">
                    <label for="shipping_city">City</label>
                    <input id="shipping_city" name="shipping_city" type="text" autocomplete="address-level2" required maxlength="100" value="<?= $f('shipping_city') ?>"<?= error_attrs($errors, 'shipping_city') ?>>
                    <?= field_error($errors, 'shipping_city') ?>
                </div>

                <div class="field">
                    <label for="notes">Notes <span class="optional">(optional)</span></label>
                    <textarea id="notes" name="notes" rows="3" maxlength="500"<?= error_attrs($errors, 'notes') ?>><?= $f('notes') ?></textarea>
                    <?= field_error($errors, 'notes') ?>
                </div>

                <div class="pay-note"><strong>Payment: cash on delivery.</strong> You pay when your order arrives.</div>

                <button class="btn btn-primary btn-block" type="submit">Place order (<?= e(money($cart['total'])) ?>)</button>
            </form>

            <aside class="summary-card" aria-label="Order summary">
                <h2>Your order</h2>
                <ul class="summary-items">
                    <?php foreach ($cart['lines'] as $line): ?>
                        <li><span><?= e($line['name']) ?> <span class="muted">&times; <?= e($line['qty']) ?></span></span><span><?= e(money((int) $line['line_total'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <dl class="summary-rows">
                    <div><dt>Subtotal</dt><dd><?= e(money($cart['subtotal'])) ?></dd></div>
                    <div><dt>Delivery</dt><dd><?= $cart['delivery'] > 0 ? e(money($cart['delivery'])) : 'Free' ?></dd></div>
                    <div class="summary-total"><dt>Total</dt><dd><?= e(money($cart['total'])) ?></dd></div>
                </dl>
                <p class="summary-more"><a href="<?= e(url('/cart')) ?>">Edit cart</a></p>
            </aside>
        </div>
    </div>
</section>
