<?php
/** @var array<string, mixed> $order */
use App\Models\Order;

$labels = ['pending' => ['New', 'badge-warn'], 'confirmed' => ['Confirmed', 'badge-ok'], 'completed' => ['Completed', 'badge-ok'], 'cancelled' => ['Cancelled', 'badge-off']];
[$label, $class] = $labels[$order['status']] ?? [(string) $order['status'], 'badge-off'];
$next = Order::TRANSITIONS[$order['status']] ?? [];
$buttons = ['confirmed' => ['Confirm order', 'btn-primary', null], 'completed' => ['Mark as completed (delivered and paid)', 'btn-primary', null], 'cancelled' => ['Cancel order', 'btn-secondary', 'Cancel this order? The stock will be put back.']];
$when = static fn (?string $v): string => $v ? date('j M Y, g:i a', strtotime($v)) : '';

$isEasy  = $order['payment_method'] === 'easypaisa';
$total   = (int) $order['total_paisa'];
$paid    = $isEasy ? (int) $order['paid_paisa'] : 0;
$balance = max(0, $total - $paid);
$hasProof = $isEasy && !empty($order['payment_proof']);
$proofUrl = url('/admin/orders/' . $order['id'] . '/proof');
$email = trim((string) $order['customer_email']);
?>
<?= partial('admin/_nav', ['active' => 'orders']) ?>

<section class="page-head">
    <div class="container">
        <h1>Order <?= e($order['order_number']) ?></h1>
        <p class="page-head-sub"><span class="badge <?= e($class) ?>"><?= e($label) ?></span> <?php if ($isEasy): ?><span class="badge badge-ep">EasyPaisa</span> <?php endif; ?>Placed <?= e($when($order['placed_at'])) ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <p><a href="<?= e(url('/admin/orders')) ?>">&larr; All orders</a></p>

        <div class="cart-layout">
            <div class="summary-card">
                <h2>Items</h2>
                <ul class="summary-items">
                    <?php foreach ($order['items'] as $item): ?>
                        <li><span><?= e($item['product_name']) ?> <span class="muted">&times; <?= e($item['quantity']) ?> at <?= e(money((int) $item['unit_price_paisa'])) ?></span></span><span><?= e(money((int) $item['line_total_paisa'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <dl class="summary-rows">
                    <div><dt>Subtotal</dt><dd><?= e(money((int) $order['subtotal_paisa'])) ?></dd></div>
                    <div><dt>Delivery</dt><dd><?= (int) $order['delivery_paisa'] > 0 ? e(money((int) $order['delivery_paisa'])) : 'Free' ?></dd></div>
                    <div class="summary-total"><dt>Order total</dt><dd><?= e(money($total)) ?></dd></div>
                    <?php if ($isEasy): ?>
                        <div><dt>Sent by EasyPaisa (customer says)</dt><dd><?= e(money($paid)) ?></dd></div>
                    <?php endif; ?>
                    <div class="summary-total"><dt>Collect on delivery</dt><dd><?= e(money($balance)) ?></dd></div>
                </dl>

                <h3>Timeline</h3>
                <ul class="timeline">
                    <li>Placed: <?= e($when($order['placed_at'])) ?></li>
                    <?php if ($order['confirmed_at']): ?><li>Confirmed: <?= e($when($order['confirmed_at'])) ?></li><?php endif; ?>
                    <?php if ($order['completed_at']): ?><li>Completed: <?= e($when($order['completed_at'])) ?></li><?php endif; ?>
                    <?php if ($order['cancelled_at']): ?><li>Cancelled: <?= e($when($order['cancelled_at'])) ?></li><?php endif; ?>
                </ul>
            </div>

            <aside class="summary-card">
                <h2>Customer</h2>
                <p>
                    <strong><?= e($order['customer_name']) ?></strong><?= $order['contact_id'] === null ? ' <span class="muted">(guest, no account)</span>' : '' ?><br>
                    <a href="tel:<?= e($order['customer_phone']) ?>"><?= e($order['customer_phone']) ?></a><br>
                    <?php if ($email !== ''): ?>
                        <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
                    <?php else: ?>
                        <span class="muted">No email given</span>
                    <?php endif; ?>
                </p>
                <h3>Deliver to</h3>
                <p><?= e($order['shipping_address']) ?><br><?= e($order['shipping_city']) ?></p>
                <?php if (!empty($order['notes'])): ?>
                    <h3>Customer notes</h3>
                    <p><?= nl2br(e($order['notes'])) ?></p>
                <?php endif; ?>

                <h3>Payment</h3>
                <?php if ($isEasy): ?>
                    <p>EasyPaisa. The customer says they sent <strong><?= e(money($paid)) ?></strong>. Compare the screenshot with your EasyPaisa account before you confirm the order.</p>
                    <?php if ($hasProof): ?>
                        <a class="proof-link" href="<?= e($proofUrl) ?>" target="_blank" rel="noopener">
                            <img class="proof-img" src="<?= e($proofUrl) ?>" alt="EasyPaisa payment screenshot for order <?= e($order['order_number']) ?>">
                        </a>
                        <p class="field-hint">Click the screenshot to open it full size.</p>
                    <?php else: ?>
                        <p class="muted">No screenshot is stored for this order.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Cash on delivery. Collect <strong><?= e(money($total)) ?></strong> when the order is delivered.</p>
                <?php endif; ?>

                <h3>Update status</h3>
                <?php if ($next === []): ?>
                    <p class="muted">This order is <?= e(strtolower($label)) ?> and cannot be changed.</p>
                <?php else: ?>
                    <?php foreach ($next as $status): [$text, $btn, $confirm] = $buttons[$status]; ?>
                        <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/status')) ?>" class="status-form"<?= $confirm ? ' data-confirm="' . e($confirm) . '"' : '' ?>>
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="<?= e($status) ?>">
                            <button class="btn <?= e($btn) ?> btn-block" type="submit"><?= e($text) ?></button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>
