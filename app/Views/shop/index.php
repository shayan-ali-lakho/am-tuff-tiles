<?php
/**
 * @var list<array<string, mixed>> $products
 * @var list<array<string, mixed>> $categories
 * @var array{sizes: list<string>, materials: list<string>} $options
 * @var array<string, mixed> $filters
 * @var string $sort
 * @var ?array<string, mixed> $activeCategory
 * @var int $total
 * @var int $page
 * @var int $pages
 */
$priceText = static fn (?int $paisa): string => $paisa !== null && $paisa > 0 ? price_input($paisa) : '';

// Query string that keeps the current filters (used by the pager and the clear links)
$params = array_filter([
    'q'        => $filters['q'],
    'category' => $filters['category'],
    'min'      => $priceText($filters['min']),
    'max'      => $priceText($filters['max']),
    'size'     => $filters['size'],
    'material' => $filters['material'],
    'instock'  => $filters['instock'] ? '1' : '',
    'sort'     => $sort !== 'newest' ? $sort : '',
], static fn ($v): bool => $v !== '');

$activeCount = count(array_diff_key($params, ['sort' => true]));
$pageUrl = static fn (int $n): string => url('/shop?' . http_build_query($params + ($n > 1 ? ['page' => $n] : [])));

$sortLabels = [
    'newest'     => 'Newest first',
    'price_asc'  => 'Price: low to high',
    'price_desc' => 'Price: high to low',
    'name'       => 'Name A to Z',
];
?>
<section class="filter-bar" aria-label="Product filters">
    <div class="container">
        <button type="button" class="filter-toggle" aria-expanded="false" aria-controls="filter-panel">
            Filters<?php if ($activeCount > 0): ?> <span class="filter-count"><?= e($activeCount) ?></span><?php endif; ?>
        </button>

        <form method="get" action="<?= e(url('/shop')) ?>" id="filter-panel" class="form filter-panel">
            <div class="field field-wide">
                <label for="f-q">Search</label>
                <input id="f-q" name="q" type="search" maxlength="100" placeholder="Search products" value="<?= e($filters['q']) ?>">
            </div>

            <div class="field">
                <label for="f-category">Category</label>
                <select id="f-category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['slug']) ?>"<?= $category['slug'] === $filters['category'] ? ' selected' : '' ?>>
                            <?= e($category['name']) ?> (<?= e($category['product_count']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field field-price">
                <label for="f-min">Price (PKR)</label>
                <div class="price-range">
                    <input id="f-min" name="min" type="text" inputmode="decimal" placeholder="Min" aria-label="Minimum price" value="<?= e($priceText($filters['min'])) ?>">
                    <span aria-hidden="true">to</span>
                    <input id="f-max" name="max" type="text" inputmode="decimal" placeholder="Max" aria-label="Maximum price" value="<?= e($priceText($filters['max'])) ?>">
                </div>
            </div>

            <?php if ($options['sizes'] !== []): ?>
                <div class="field">
                    <label for="f-size">Size</label>
                    <select id="f-size" name="size">
                        <option value="">Any size</option>
                        <?php foreach ($options['sizes'] as $size): ?>
                            <option value="<?= e($size) ?>"<?= $size === $filters['size'] ? ' selected' : '' ?>><?= e($size) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if ($options['materials'] !== []): ?>
                <div class="field">
                    <label for="f-material">Material</label>
                    <select id="f-material" name="material">
                        <option value="">Any material</option>
                        <?php foreach ($options['materials'] as $material): ?>
                            <option value="<?= e($material) ?>"<?= $material === $filters['material'] ? ' selected' : '' ?>><?= e($material) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="field">
                <label for="f-sort">Sort by</label>
                <select id="f-sort" name="sort">
                    <?php foreach ($sortLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $key === $sort ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field field-check">
                <label class="check"><input type="checkbox" name="instock" value="1"<?= $filters['instock'] ? ' checked' : '' ?>> In stock only</label>
            </div>

            <div class="filter-actions">
                <button class="btn btn-primary btn-sm" type="submit">Apply</button>
                <?php if ($activeCount > 0 || $sort !== 'newest'): ?>
                    <a class="btn btn-ghost-dark btn-sm" href="<?= e(url('/shop')) ?>">Clear all</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>

<section class="section shop-section">
    <div class="container">
        <div class="shop-head">
            <h1><?= e($activeCategory['name'] ?? 'Shop') ?></h1>
            <p class="muted" aria-live="polite">
                <?= e($total) ?> <?= $total === 1 ? 'product' : 'products' ?><?= $activeCount > 0 ? ' found' : '' ?>
            </p>
        </div>

        <?php if ($activeCategory !== null && !empty($activeCategory['description'])): ?>
            <p class="shop-intro"><?= e($activeCategory['description']) ?></p>
        <?php endif; ?>

        <?php if ($products === []): ?>
            <div class="empty-state">
                <?php if ($activeCount > 0): ?>
                    <p>No products match your filters.</p>
                    <p><a class="btn btn-primary" href="<?= e(url('/shop')) ?>">Clear filters</a></p>
                <?php else: ?>
                    <p>Products are coming soon. Please check back shortly.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <ul class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?= partial('shop/_card', ['product' => $product]) ?>
                <?php endforeach; ?>
            </ul>

            <?php if ($pages > 1): ?>
                <nav class="pager" aria-label="Pages">
                    <?php if ($page > 1): ?>
                        <a class="btn btn-secondary btn-sm" href="<?= e($pageUrl($page - 1)) ?>" rel="prev">Previous</a>
                    <?php endif; ?>
                    <span class="pager-info">Page <?= e($page) ?> of <?= e($pages) ?></span>
                    <?php if ($page < $pages): ?>
                        <a class="btn btn-secondary btn-sm" href="<?= e($pageUrl($page + 1)) ?>" rel="next">Next</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
