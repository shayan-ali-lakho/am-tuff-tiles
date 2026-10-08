<?php /** @var string $active */ ?>
<nav class="admin-nav" aria-label="Admin">
    <div class="container">
        <a href="<?= e(url('/admin')) ?>"<?= $active === 'dashboard' ? ' class="is-active" aria-current="page"' : '' ?>>Dashboard</a>
        <a href="<?= e(url('/admin/orders')) ?>"<?= $active === 'orders' ? ' class="is-active" aria-current="page"' : '' ?>>Orders</a>
        <a href="<?= e(url('/admin/products')) ?>"<?= $active === 'products' ? ' class="is-active" aria-current="page"' : '' ?>>Products</a>
        <a href="<?= e(url('/admin/categories')) ?>"<?= $active === 'categories' ? ' class="is-active" aria-current="page"' : '' ?>>Categories</a>
        <a href="<?= e(url('/admin/users')) ?>"<?= $active === 'users' ? ' class="is-active" aria-current="page"' : '' ?>>Users</a>
        <a href="<?= e(url('/admin/reports')) ?>"<?= $active === 'reports' ? ' class="is-active" aria-current="page"' : '' ?>>Monthly report</a>
    </div>
</nav>
