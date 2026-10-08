<?php
/**
 * @var array<string, mixed> $product       empty when adding
 * @var list<array<string, mixed>> $images
 * @var list<array<string, mixed>> $categories
 * @var array<string, string> $errors
 * @var array<string, string> $old
 * @var int $maxImages
 */
$isEdit = $product !== [];
$value = static function (string $key, string $default = '') use ($old, $product): string {
    if (array_key_exists($key, $old)) {
        return (string) $old[$key];
    }

    return isset($product[$key]) ? (string) $product[$key] : $default;
};
$priceValue = array_key_exists('price', $old)
    ? $old['price']
    : (isset($product['price_paisa']) ? price_input((int) $product['price_paisa']) : '');
$isChecked = static function (string $key, bool $default) use ($old, $product): bool {
    if (array_key_exists($key, $old)) {
        return $old[$key] === '1';
    }

    return isset($product[$key]) ? (int) $product[$key] === 1 : $default;
};
$action = $isEdit ? '/admin/products/' . (int) $product['id'] : '/admin/products';
$remaining = max(0, $maxImages - count($images));
?>
<?= partial('admin/_nav', ['active' => 'products']) ?>

<section class="page-head">
    <div class="container">
        <h1><?= $isEdit ? 'Edit product' : 'Add product' ?></h1>
        <p class="page-head-sub"><a class="link-light" href="<?= e(url('/admin/products')) ?>">&larr; Back to products</a></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="form panel" novalidate>
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="field full">
                    <label for="name">Product name</label>
                    <input id="name" name="name" type="text" maxlength="190" required value="<?= e($value('name')) ?>"<?= error_attrs($errors, 'name') ?>>
                    <?= field_error($errors, 'name') ?>
                </div>

                <div class="field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required<?= error_attrs($errors, 'category_id') ?>>
                        <option value="">Choose a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category['id']) ?>"<?= $value('category_id') === (string) $category['id'] ? ' selected' : '' ?>>
                                <?= e($category['name']) ?><?= (int) $category['is_active'] === 1 ? '' : ' (turned off)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'category_id') ?>
                </div>

                <div class="field">
                    <label for="price">Price (PKR)</label>
                    <input id="price" name="price" type="text" inputmode="decimal" required placeholder="1250" value="<?= e($priceValue) ?>"<?= error_attrs($errors, 'price') ?>>
                    <?= field_error($errors, 'price') ?>
                </div>

                <div class="field">
                    <label for="size">Size <span class="optional">(optional)</span></label>
                    <input id="size" name="size" type="text" maxlength="80" placeholder="e.g. 30 x 30 cm" value="<?= e($value('size')) ?>"<?= error_attrs($errors, 'size') ?>>
                    <?= field_error($errors, 'size') ?>
                </div>

                <div class="field">
                    <label for="material">Material <span class="optional">(optional)</span></label>
                    <input id="material" name="material" type="text" maxlength="80" placeholder="e.g. Concrete, Steel, Wood" value="<?= e($value('material')) ?>"<?= error_attrs($errors, 'material') ?>>
                    <?= field_error($errors, 'material') ?>
                </div>

                <div class="field">
                    <label for="stock_qty">In stock</label>
                    <input id="stock_qty" name="stock_qty" type="text" inputmode="numeric" required value="<?= e($value('stock_qty', '0')) ?>"<?= error_attrs($errors, 'stock_qty') ?>>
                    <p class="field-hint">Number of items available. 0 shows "Out of stock".</p>
                    <?= field_error($errors, 'stock_qty') ?>
                </div>

                <div class="field full">
                    <label for="short_description">Short description <span class="optional">(optional)</span></label>
                    <input id="short_description" name="short_description" type="text" maxlength="255" value="<?= e($value('short_description')) ?>"<?= error_attrs($errors, 'short_description') ?>>
                    <p class="field-hint">One line shown on the product card in the shop.</p>
                    <?= field_error($errors, 'short_description') ?>
                </div>

                <div class="field full">
                    <label for="description">Full description <span class="optional">(optional)</span></label>
                    <textarea id="description" name="description" rows="6" maxlength="5000"<?= error_attrs($errors, 'description') ?>><?= e($value('description')) ?></textarea>
                    <?= field_error($errors, 'description') ?>
                </div>

                <div class="field full">
                    <label for="images"><?= $isEdit ? 'Add more photos' : 'Photos' ?> <span class="optional">(optional)</span></label>
                    <?php if ($remaining > 0): ?>
                        <input id="images" name="images[]" type="file" multiple accept="image/jpeg,image/png,image/webp">
                        <p class="field-hint">JPG, PNG or WebP, up to 10 MB each, <?= e($remaining) ?> more allowed. The first photo becomes the main photo. Photos are resized automatically.</p>
                    <?php else: ?>
                        <p class="field-hint">This product already has the maximum of <?= e($maxImages) ?> photos. Delete one below to add another.</p>
                    <?php endif; ?>
                </div>

                <div class="field full checks">
                    <label class="check"><input type="checkbox" name="is_active" value="1"<?= $isChecked('is_active', true) ? ' checked' : '' ?>> Visible in the shop</label>
                    <label class="check"><input type="checkbox" name="is_featured" value="1"<?= $isChecked('is_featured', false) ? ' checked' : '' ?>> Featured product</label>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save changes' : 'Add product' ?></button>
                <a class="btn btn-secondary" href="<?= e(url('/admin/products')) ?>">Cancel</a>
            </div>
        </form>

        <?php if ($isEdit): ?>
            <div class="panel">
                <h2>Photos</h2>
                <?php if ($images === []): ?>
                    <p class="muted">No photos yet. Use "Add more photos" above and save.</p>
                <?php else: ?>
                    <ul class="photo-grid">
                        <?php foreach ($images as $image): ?>
                            <li class="photo">
                                <a href="<?= e(upload_url((string) $image['file_path'])) ?>" target="_blank" rel="noopener">
                                    <img src="<?= e(upload_url((string) $image['file_path'], true)) ?>" alt="Photo of <?= e($product['name']) ?>" loading="lazy">
                                </a>
                                <div class="photo-actions">
                                    <?php if ((int) $image['is_primary'] === 1): ?>
                                        <span class="badge badge-ok">Main photo</span>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url('/admin/products/' . (int) $product['id'] . '/images/' . (int) $image['id'] . '/primary')) ?>" class="inline-form">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-secondary btn-sm" type="submit">Make main</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(url('/admin/products/' . (int) $product['id'] . '/images/' . (int) $image['id'] . '/delete')) ?>" class="inline-form" data-confirm="Delete this photo?">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="panel panel-danger">
                <h2>Delete product</h2>
                <p>Deleting removes this product and its photos for good. Past orders keep their own copy of the name and price.
                   If you only want it off the shop for now, untick "Visible in the shop" instead.</p>
                <form method="post" action="<?= e(url('/admin/products/' . (int) $product['id'] . '/delete')) ?>" data-confirm="Delete this product and all its photos? This cannot be undone.">
                    <?= csrf_field() ?>
                    <button class="btn btn-danger" type="submit">Delete product</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>
