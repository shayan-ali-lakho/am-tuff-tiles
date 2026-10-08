<?php
/**
 * @var list<array<string, mixed>> $products
 * @var list<array<string, mixed>> $categories
 * @var array{q: string, category: int, status: string} $filters
 * @var int $total
 * @var int $page
 * @var int $pages
 */
$query = static fn (array $extra = []): string => http_build_query(array_filter(
    array_merge(['q' => $filters['q'], 'category' => $filters['category'] ?: '', 'status' => $filters['status']], $extra),
    static fn ($v): bool => $v !== '' && $v !== null
));
$back = '/admin/products' . ($query(['page' => $page > 1 ? $page : '']) !== '' ? '?' . $query(['page' => $page > 1 ? $page : '']) : '');
$hasFilters = $filters['q'] !== '' || $filters['category'] > 0 || $filters['status'] !== '';
?>
<?= partial('admin/_nav', ['active' => 'products']) ?>

<section class="page-head">
    <div class="container">
        <h1>Products</h1>
        <p class="page-head-sub"><?= e($total) ?> <?= $total === 1 ? 'product' : 'products' ?><?= $hasFilters ? ' match your filters' : '' ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="toolbar">
            <form method="get" action="<?= e(url('/admin/products')) ?>" class="form filter-form">
                <div class="field">
                    <label for="q">Search</label>
                    <input id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Product name">
                </div>
                <div class="field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category['id']) ?>"<?= (int) $category['id'] === $filters['category'] ? ' selected' : '' ?>><?= e($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="status">Show</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <option value="active"<?= $filters['status'] === 'active' ? ' selected' : '' ?>>Visible in shop</option>
                        <option value="hidden"<?= $filters['status'] === 'hidden' ? ' selected' : '' ?>>Hidden</option>
                        <option value="out"<?= $filters['status'] === 'out' ? ' selected' : '' ?>>Out of stock</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-secondary btn-sm" type="submit">Filter</button>
                    <?php if ($hasFilters): ?>
                        <a class="btn btn-ghost-dark btn-sm" href="<?= e(url('/admin/products')) ?>">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <a class="btn btn-primary" href="<?= e(url('/admin/products/new')) ?>">Add product</a>
        </div>

        <?php if ($products === []): ?>
            <div class="empty-state">
                <?php if ($hasFilters): ?>
                    <p>No products match your filters.</p>
                <?php else: ?>
                    <p>No products yet. Add your first product to get started.</p>
                    <p><a class="btn btn-primary" href="<?= e(url('/admin/products/new')) ?>">Add product</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Photo</th>
                            <th scope="col">Product</th>
                            <th scope="col">Category</th>
                            <th scope="col">Price</th>
                            <th scope="col">Stock</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php $active = (int) $product['is_active'] === 1; ?>
                        <tr>
                            <td data-label="Photo">
                                <?php if (!empty($product['image_path'])): ?>
                                    <img class="thumb" src="<?= e(upload_url((string) $product['image_path'], true)) ?>" alt="" width="64" height="64" loading="lazy">
                                <?php else: ?>
                                    <span class="thumb thumb-empty" aria-label="No photo">No photo</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Product">
                                <a class="table-title" href="<?= e(url('/admin/products/' . (int) $product['id'] . '/edit')) ?>"><?= e($product['name']) ?></a>
                                <?php if ((int) $product['is_featured'] === 1): ?><span class="badge badge-info">Featured</span><?php endif; ?>
                            </td>
                            <td data-label="Category"><?= e($product['category_name']) ?></td>
                            <td data-label="Price"><?= e(money((int) $product['price_paisa'])) ?></td>
                            <td data-label="Stock">
                                <?php if ((int) $product['stock_qty'] === 0): ?>
                                    <span class="badge badge-warn">Out of stock</span>
                                <?php else: ?>
                                    <?= e($product['stock_qty']) ?>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status">
                                <span class="badge <?= $active ? 'badge-ok' : 'badge-off' ?>"><?= $active ? 'Visible' : 'Hidden' ?></span>
                            </td>
                            <td class="actions">
                                <a class="btn btn-secondary btn-sm" href="<?= e(url('/admin/products/' . (int) $product['id'] . '/edit')) ?>">Edit</a>
                                <form method="post" action="<?= e(url('/admin/products/' . (int) $product['id'] . '/toggle')) ?>" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="back" value="<?= e($back) ?>">
                                    <button class="btn btn-secondary btn-sm" type="submit"><?= $active ? 'Hide' : 'Show' ?></button>
                                </form>
                                <form method="post" action="<?= e(url('/admin/products/' . (int) $product['id'] . '/delete')) ?>" class="inline-form" data-confirm="Delete &quot;<?= e($product['name']) ?>&quot; and all its photos? This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
                <nav class="pager" aria-label="Pages">
                    <?php if ($page > 1): ?>
                        <a class="btn btn-secondary btn-sm" href="<?= e(url('/admin/products?' . $query(['page' => $page - 1]))) ?>">Previous</a>
                    <?php endif; ?>
                    <span class="pager-info">Page <?= e($page) ?> of <?= e($pages) ?></span>
                    <?php if ($page < $pages): ?>
                        <a class="btn btn-secondary btn-sm" href="<?= e(url('/admin/products?' . $query(['page' => $page + 1]))) ?>">Next</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
