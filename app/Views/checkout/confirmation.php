<?php
/**
 * @var array<string, mixed> $order
 * @var bool $isGuest
 */
$isEasy  = $order['payment_method'] === 'easypaisa';
$total   = (int) $order['total_paisa'];
$paid    = $isEasy ? (int) $order['paid_paisa'] : 0;
$balance = max(0, $total - $paid);
?>
<section class="page-head">
    <div class="container"><h1>Thank you, your order is placed</h1></div>
</section>

<section class="section">
    <div class="container narrow">
        <div class="alert alert-success" role="status">
            Your order number is <strong><?= e($order['order_number']) ?></strong>. We will call you on
            <?= e($order['customer_phone']) ?> to confirm.
            <?php if ($isEasy): ?>
                We will also check your EasyPaisa screenshot.<?= $balance > 0 ? ' Please keep the remaining ' . e(money($balance)) . ' ready in cash for delivery.' : '' ?>
            <?php else: ?>
                Please keep the cash ready for delivery.
            <?php endif; ?>
        </div>

        <?php if ($isGuest): ?>
            <p class="muted">Please note down your order number. You can see this page again in this browser, but you do not have an account, so it will not be listed anywhere else.</p>
        <?php endif; ?>

        <div class="summary-card summary-wide">
            <h2>Order details</h2>
            <ul class="summary-items">
                <?php foreach ($order['items'] as $item): ?>
                    <li><span><?= e($item['product_name']) ?> <span class="muted">&times; <?= e($item['quantity']) ?></span></span><span><?= e(money((int) $item['line_total_paisa'])) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <dl class="summary-rows">
                <div><dt>Subtotal</dt><dd><?= e(money((int) $order['subtotal_paisa'])) ?></dd></div>
                <div><dt>Delivery</dt><dd><?= (int) $order['delivery_paisa'] > 0 ? e(money((int) $order['delivery_paisa'])) : 'Free' ?></dd></div>
                <div class="summary-total"><dt>Order total</dt><dd><?= e(money($total)) ?></dd></div>
            </dl>

            <?php if ($isEasy): ?>
                <div class="pay-summary">
                    <p><strong>Paid by EasyPaisa:</strong> <?= e(money($paid)) ?> <span class="muted">(waiting for our check)</span></p>
                    <p><strong>To pay on delivery (cash):</strong> <?= e(money($balance)) ?></p>
                </div>
            <?php else: ?>
                <div class="pay-summary">
                    <p><strong>To pay on delivery (cash):</strong> <?= e(money($total)) ?></p>
                </div>
            <?php endif; ?>

            <h3>Delivering to</h3>
            <p><?= e($order['customer_name']) ?><br><?= e($order['shipping_address']) ?>, <?= e($order['shipping_city']) ?></p>
            <?php if (!empty($order['notes'])): ?>
                <h3>Your notes</h3>
                <p><?= nl2br(e($order['notes'])) ?></p>
            <?php endif; ?>
        </div>

        <p class="summary-more">
            <a class="btn btn-primary" href="<?= e(url('/shop')) ?>">Continue shopping</a>
            <?php if (!$isGuest): ?>
                <a class="btn btn-secondary" href="<?= e(url('/orders')) ?>">My orders</a>
            <?php endif; ?>
        </p>
    </div>
</section>
