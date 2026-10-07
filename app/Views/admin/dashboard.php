<?php /** @var array $admin */ ?>
<section class="page-head">
    <div class="container">
        <h1>Admin panel</h1>
        <p class="page-head-sub">Signed in as <?= e($admin['full_name'] ?? '') ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <ul class="card-grid">
            <li class="card">
                <h3>Orders</h3>
                <p>Orders received and completed.</p>
                <span class="badge">Coming soon</span>
            </li>
            <li class="card">
                <h3>Products</h3>
                <p>Upload images, set names, prices and details.</p>
                <span class="badge">Coming soon</span>
            </li>
            <li class="card">
                <h3>Monthly report</h3>
                <p>Sales and orders for each month.</p>
                <span class="badge">Coming soon</span>
            </li>
        </ul>
    </div>
</section>
