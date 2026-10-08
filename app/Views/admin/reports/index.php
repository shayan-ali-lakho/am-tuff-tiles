<?php
/**
 * @var string $month
 * @var string $label
 * @var list<string> $months
 * @var array<string, mixed> $report
 */
$max = 0;
foreach ($report['days'] as $d) { $max = max($max, $d['revenue']); }
?>
<?= partial('admin/_nav', ['active' => 'reports']) ?>

<section class="page-head">
    <div class="container">
        <h1>Monthly report</h1>
        <p class="page-head-sub"><?= e($label) ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="toolbar">
            <form method="get" action="<?= e(url('/admin/reports')) ?>" class="form filter-form">
                <div class="field">
                    <label for="month">Month</label>
                    <select id="month" name="month">
                        <?php foreach ($months as $m): ?>
                            <option value="<?= e($m) ?>"<?= $m === $month ? ' selected' : '' ?>><?= e(date('F Y', strtotime($m . '-01'))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary btn-sm" type="submit">Show</button>
                <a class="btn btn-secondary btn-sm" href="<?= e(url('/admin/reports/export?month=' . $month)) ?>">Download CSV</a>
            </form>
        </div>

        <ul class="stat-grid">
            <li class="stat-card"><span class="stat-label">Money earned</span><strong><?= e(money($report['revenue'])) ?></strong><span class="muted">from completed orders</span></li>
            <li class="stat-card"><span class="stat-label">Orders completed</span><strong><?= e($report['completed']) ?></strong><span class="muted">average <?= e(money($report['average'])) ?></span></li>
            <li class="stat-card"><span class="stat-label">Orders received</span><strong><?= e($report['placed']) ?></strong><span class="muted"><?= e($report['by_status']['pending'] + $report['by_status']['confirmed']) ?> still open, <?= e($report['by_status']['cancelled']) ?> cancelled</span></li>
            <li class="stat-card"><span class="stat-label">Goods / delivery</span><strong><?= e(money($report['goods'])) ?></strong><span class="muted">+ <?= e(money($report['delivery'])) ?> delivery</span></li>
        </ul>
        <p class="field-hint">Money earned counts orders by the day they were completed. Orders received counts orders by the day they were placed.</p>

        <h2 class="report-h">Best sellers</h2>
        <?php if ($report['top'] === []): ?>
            <div class="empty-state"><p>No completed orders in <?= e($label) ?>.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Product</th><th>Sold</th><th>Money</th></tr></thead>
                    <tbody>
                    <?php foreach ($report['top'] as $row): ?>
                        <tr><td><?= e($row['product_name']) ?></td><td><?= e($row['qty']) ?></td><td><?= e(money((int) $row['revenue'])) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 class="report-h">Day by day</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Day</th><th>Received</th><th>Completed</th><th>Money earned</th><th class="bar-col"><span class="sr-only">Chart</span></th></tr></thead>
                <tbody>
                <?php foreach ($report['days'] as $day => $d): if ($d['placed'] === 0 && $d['completed'] === 0) { continue; } ?>
                    <tr>
                        <td><?= e(date('D j M', strtotime($day))) ?></td>
                        <td><?= e($d['placed']) ?></td>
                        <td><?= e($d['completed']) ?></td>
                        <td><?= e(money($d['revenue'])) ?></td>
                        <td class="bar-col"><span class="bar" style="width: <?= $max > 0 ? e(round($d['revenue'] / $max * 100)) : 0 ?>%"></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($report['placed'] === 0 && $report['completed'] === 0): ?>
                    <tr><td colspan="5" class="muted">Nothing happened this month yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
