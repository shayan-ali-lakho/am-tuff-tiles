<?php
/**
 * @var list<array<string, mixed>> $orders
 * @var array{tab: string, q: string} $filters
 * @var array<string, int> $counts
 * @var int $total
 * @var int $page
 * @var int $pages
 */
$tabs = [
    'received'  => ['Received', $counts['pending'] + $counts['confirmed']],
    'completed' => ['Completed', $counts['completed']],
    'cancelled' => ['Cancelled', $counts['cancelled']],
    'all'       => ['All', array_sum($counts)],
];
$labels = ['pending' => ['New', 'badge-warn'], 'confirmed' => ['Confirmed', 'badge-ok'], 'completed' => ['Completed', 'badge-ok'], 'cancelled' => ['Cancelled', 'badge-off']];
$pageUrl = static fn (int $n): string => url('/admin/orders?' . http_build_query(array_filter(['tab' => $filters['tab'], 'q' => $filters['q'], 'page' => $n > 1 ? $n : ''], static fn ($v): bool => $v !== '')));
?>
<?= partial('admin/_nav', ['active' => 'orders']) ?>

<section class="page-head">
    <div class="container">
        <h1>Orders</h1>
        <p class="page-head-sub">Open an order to check the payment, then confirm, complete or cancel it.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="tabs" role="tablist">
            <?php foreach ($tabs as $key => [$label, $n]): ?>
                <a href="<?= e(url('/admin/orders?tab=' . $key)) ?>" class="tab<?= $filters['tab'] === $key ? ' is-active' : '' ?>"<?= $filters['tab'] === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="tab-count"><?= e($n) ?></span></a>
            <?php endforeach; ?>
        </div>

        <div class="toolbar">
            <form method="get" action="<?= e(url('/admin/orders')) ?>" class="form filter-form">
                <input type="hidden" name="tab" value="<?= e($filters['tab']) ?>">
                <div class="field">
                    <label for="q">Search</label>
                    <input id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Order number, name or phone">
                </div>
                <button class="btn btn-primary btn-sm" type="submit">Search</button>
                <?php if ($filters['q'] !== ''): ?>
                    <a class="btn btn-secondary btn-sm" href="<?= e(url('/admin/orders?tab=' . $filters['tab'])) ?>">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($orders === []): ?>
            <div class="empty-state"><p>No orders here yet.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Order</th><th>Placed</th><th>Customer</th><th>City</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): [$label, $class] = $labels[$o['status']] ?? [(string) $o['status'], 'badge-off']; ?>
                        <tr>
                            <td><a class="table-title" href="<?= e(url('/admin/orders/' . $o['id'])) ?>"><?= e($o['order_number']) ?></a></td>
                            <td><?= e(date('j M, g:i a', strtotime((string) $o['placed_at']))) ?></td>
                            <td><?= e($o['customer_name']) ?><br><span class="muted"><?= e($o['customer_phone']) ?></span></td>
                            <td><?= e($o['shipping_city']) ?></td>
                            <td><?= e($o['item_count']) ?></td>
                            <td><?= e(money((int) $o['total_paisa'])) ?></td>
                            <td>
                                <?php if ($o['payment_method'] === 'easypaisa'): ?>
                                    <span class="badge badge-ep">EasyPaisa</span><br><span class="muted"><?= e(money((int) $o['paid_paisa'])) ?> sent</span>
                                <?php else: ?>
                                    Cash
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= e($class) ?>"><?= e($label) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
                <nav class="pager" aria-label="Pages">
                    <?php if ($page > 1): ?><a class="btn btn-secondary btn-sm" href="<?= e($pageUrl($page - 1)) ?>" rel="prev">Previous</a><?php endif; ?>
                    <span class="pager-info">Page <?= e($page) ?> of <?= e($pages) ?></span>
                    <?php if ($page < $pages): ?><a class="btn btn-secondary btn-sm" href="<?= e($pageUrl($page + 1)) ?>" rel="next">Next</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
