<?php
/**
 * @var array $admin
 * @var ?array{counts: array<string,int>, done: int, revenue: int, products: int, low: int} $stats
 */
?>
<?= partial('admin/_nav', ['active' => 'dashboard']) ?>

<section class="page-head">
    <div class="container">
        <h1>Admin panel</h1>
        <p class="page-head-sub">Signed in as <?= e($admin['full_name'] ?? '') ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <ul class="card-grid card-grid-center">
            <li class="card">
                <h3>Orders</h3>
                <?php if ($stats !== null): ?>
                    <p class="stat"><strong><?= e($stats['counts']['pending']) ?></strong> new &middot; <strong><?= e($stats['counts']['confirmed']) ?></strong> confirmed &middot; <strong><?= e($stats['counts']['completed']) ?></strong> completed</p>
                <?php else: ?>
                    <p>Orders received and completed.</p>
                <?php endif; ?>
                <a class="btn btn-primary btn-sm card-link" href="<?= e(url('/admin/orders')) ?>">View orders</a>
            </li>
            <li class="card">
                <h3>Products</h3>
                <?php if ($stats !== null): ?>
                    <p class="stat"><strong><?= e($stats['products']) ?></strong> products<?= $stats['low'] > 0 ? ' &middot; <strong>' . e($stats['low']) . '</strong> low or out of stock' : '' ?></p>
                <?php else: ?>
                    <p>Upload images, set names, prices and details.</p>
                <?php endif; ?>
                <a class="btn btn-primary btn-sm card-link" href="<?= e(url('/admin/products')) ?>">Manage products</a>
            </li>
            <li class="card">
                <h3>Monthly report</h3>
                <?php if ($stats !== null): ?>
                    <p class="stat">This month: <strong><?= e(money($stats['revenue'])) ?></strong> from <strong><?= e($stats['done']) ?></strong> completed orders</p>
                <?php else: ?>
                    <p>Sales and orders for each month.</p>
                <?php endif; ?>
                <a class="btn btn-primary btn-sm card-link" href="<?= e(url('/admin/reports')) ?>">Open report</a>
            </li>
        </ul>
    </div>
</section>
