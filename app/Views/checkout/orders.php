<?php /** @var list<array<string, mixed>> $orders */
$labels = ['pending' => ['Received', 'badge-warn'], 'confirmed' => ['Confirmed', 'badge-ok'], 'completed' => ['Completed', 'badge-ok'], 'cancelled' => ['Cancelled', 'badge-off']];
?>
<section class="page-head">
    <div class="container"><h1>My orders</h1></div>
</section>

<section class="section">
    <div class="container">
        <?php if ($orders === []): ?>
            <div class="empty-state">
                <p>You have not placed any orders yet.</p>
                <a class="btn btn-primary" href="<?= e(url('/shop')) ?>">Browse the shop</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): [$label, $class] = $labels[$o['status']] ?? [ucfirst((string) $o['status']), 'badge-off']; ?>
                        <tr>
                            <td><a class="table-title" href="<?= e(url('/order/' . $o['order_number'])) ?>"><?= e($o['order_number']) ?></a></td>
                            <td><?= e(date('j M Y', strtotime((string) $o['placed_at']))) ?></td>
                            <td><?= e($o['item_count']) ?></td>
                            <td><?= e(money((int) $o['total_paisa'])) ?></td>
                            <td><span class="badge <?= e($class) ?>"><?= e($label) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
