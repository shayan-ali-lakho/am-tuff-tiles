<?php /** @var string $active */ ?>
<nav class="admin-nav" aria-label="Admin">
    <div class="container">
        <a href="<?= e(url('/admin')) ?>"<?= $active === 'dashboard' ? ' class="is-active" aria-current="page"' : '' ?>>Dashboard</a>
        <span class="is-soon" title="Coming soon">Orders</span>
        <a href="<?= e(url('/admin/products')) ?>"<?= $active === 'products' ? ' class="is-active" aria-current="page"' : '' ?>>Products</a>
        <a href="<?= e(url('/admin/categories')) ?>"<?= $active === 'categories' ? ' class="is-active" aria-current="page"' : '' ?>>Categories</a>
        <span class="is-soon" title="Coming soon">Monthly report</span>
    </div>
</nav>
