<?php /** @var array<string, mixed> $order */ ?>
<section class="page-head">
    <div class="container"><h1>Thank you, your order is placed</h1></div>
</section>

<section class="section">
    <div class="container narrow">
        <div class="alert alert-success" role="status">
            Your order number is <strong><?= e($order['order_number']) ?></strong>. We will call you on
            <?= e($order['customer_phone']) ?> to confirm. Please keep the cash ready for delivery.
        </div>

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
                <div class="summary-total"><dt>Total to pay on delivery</dt><dd><?= e(money((int) $order['total_paisa'])) ?></dd></div>
            </dl>

            <h3>Delivering to</h3>
            <p><?= e($order['customer_name']) ?><br><?= e($order['shipping_address']) ?>, <?= e($order['shipping_city']) ?></p>
            <?php if (!empty($order['notes'])): ?>
                <h3>Your notes</h3>
                <p><?= nl2br(e($order['notes'])) ?></p>
            <?php endif; ?>
        </div>

        <p class="summary-more">
            <a class="btn btn-primary" href="<?= e(url('/shop')) ?>">Continue shopping</a>
            <a class="btn btn-secondary" href="<?= e(url('/orders')) ?>">My orders</a>
        </p>
    </div>
</section>
