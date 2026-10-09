<?php
/**
 * @var array{lines: list<array<string, mixed>>, subtotal: int, delivery: int, total: int, notices: list<string>} $cart
 * @var array<string, string> $errors
 * @var array<string, string> $old
 * @var bool $isGuest
 * @var array{enabled: bool, number: string, name: string} $easypaisa
 */
$f = static fn (string $k): string => e($old[$k] ?? '');
$method = ($old['payment_method'] ?? 'cod') === 'easypaisa' && $easypaisa['enabled'] ? 'easypaisa' : 'cod';
$totalInput = price_input((int) $cart['total']);
?>
<section class="page-head">
    <div class="container"><h1>Checkout</h1></div>
</section>

<section class="section">
    <div class="container">
        <div class="cart-layout">
            <form method="post" action="<?= e(url('/checkout')) ?>" class="form checkout-form" enctype="multipart/form-data" novalidate data-checkout-form>
                <?= csrf_field() ?>
                <h2>Delivery details</h2>

                <?php if ($isGuest): ?>
                    <p class="guest-note">No account needed. Just fill in your details below to place your order.</p>
                <?php endif; ?>

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
                    <label for="customer_email">Email <span class="optional">(optional)</span></label>
                    <input id="customer_email" name="customer_email" type="email" autocomplete="email" maxlength="190" value="<?= $f('customer_email') ?>"<?= error_attrs($errors, 'customer_email') ?>>
                    <p class="field-hint">We email your order summary here.</p>
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

                <?php if ($easypaisa['enabled']): ?>
                    <fieldset class="pay-options">
                        <legend>Payment</legend>
                        <div class="pay-options-grid">
                            <label class="pay-option">
                                <input type="radio" name="payment_method" value="cod"<?= $method === 'cod' ? ' checked' : '' ?>>
                                <span class="pay-option-card">
                                    <span class="pay-dot" aria-hidden="true"></span>
                                    <span>
                                        <span class="pay-option-title">Cash on delivery</span>
                                        <span class="pay-option-text">You pay when your order arrives.</span>
                                    </span>
                                </span>
                            </label>
                            <label class="pay-option pay-option-ep">
                                <input type="radio" name="payment_method" value="easypaisa"<?= $method === 'easypaisa' ? ' checked' : '' ?>>
                                <span class="pay-option-card">
                                    <span class="pay-dot" aria-hidden="true"></span>
                                    <span>
                                        <span class="pay-option-title">EasyPaisa</span>
                                        <span class="pay-option-text">Pay an advance or the full amount, then attach a screenshot.</span>
                                    </span>
                                </span>
                            </label>
                        </div>
                        <?= field_error($errors, 'payment_method') ?>
                    </fieldset>

                    <div class="pay-panel<?= $method === 'easypaisa' ? '' : ' is-collapsed' ?>" data-pay-panel>
                        <div class="pay-panel-inner">
                            <div class="pay-panel-box">
                                <h3>Pay with EasyPaisa</h3>

                                <div class="ep-account">
                                    <span class="ep-number"><?= e($easypaisa['number']) ?></span>
                                    <?php if ($easypaisa['name'] !== ''): ?>
                                        <span class="ep-name">Account name: <?= e($easypaisa['name']) ?></span>
                                    <?php endif; ?>
                                    <button class="btn btn-secondary btn-sm ep-copy js-only" type="button" data-copy="<?= e($easypaisa['number']) ?>">Copy number</button>
                                </div>

                                <ol class="ep-steps">
                                    <li>Send the advance, or the full amount (<?= e(money($cart['total'])) ?>), to the EasyPaisa number above.</li>
                                    <li>Take a screenshot of the successful transaction.</li>
                                    <li>Enter the amount you sent and attach the screenshot below. The rest, if any, is paid in cash on delivery.</li>
                                </ol>

                                <div class="field">
                                    <label for="paid_amount">Amount you sent (PKR)</label>
                                    <div class="amount-row">
                                        <input id="paid_amount" name="paid_amount" type="text" inputmode="decimal" autocomplete="off" maxlength="12" placeholder="e.g. 1500" value="<?= $f('paid_amount') ?>"<?= error_attrs($errors, 'paid_amount') ?>>
                                        <button class="btn btn-secondary btn-sm js-only" type="button" data-fill-amount="<?= e($totalInput) ?>">Full amount</button>
                                    </div>
                                    <?= field_error($errors, 'paid_amount') ?>
                                </div>

                                <div class="field">
                                    <label for="payment_screenshot">Payment screenshot</label>
                                    <input id="payment_screenshot" name="payment_screenshot" type="file" accept="image/jpeg,image/png,image/webp"<?= error_attrs($errors, 'payment_screenshot') ?>>
                                    <p class="field-hint">A JPG or PNG screenshot of the transaction, up to 10 MB.</p>
                                    <p class="shot-note" data-shot-note aria-live="polite"></p>
                                    <div class="shot-preview" data-shot-preview><img src="" alt="Preview of your screenshot"></div>
                                    <?= field_error($errors, 'payment_screenshot') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="payment_method" value="cod">
                    <div class="pay-note"><strong>Payment: cash on delivery.</strong> You pay when your order arrives.</div>
                <?php endif; ?>

                <div class="hp-field" aria-hidden="true">
                    <label for="hp_check">Leave this field empty</label>
                    <input id="hp_check" name="hp_check" type="text" tabindex="-1" autocomplete="off">
                </div>

                <button class="btn btn-primary btn-block" type="submit" data-submit-button>Place order (<?= e(money($cart['total'])) ?>)</button>
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
