<?php
/**
 * @var list<array<string, mixed>> $categories
 * @var array<int|string, string> $errors
 * @var array<string, string> $old
 */
?>
<?= partial('admin/_nav', ['active' => 'categories']) ?>

<section class="page-head">
    <div class="container">
        <h1>Categories</h1>
        <p class="page-head-sub">Groups used to organise and filter products in the shop.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="panel">
            <h2>Add a category</h2>
            <form method="post" action="<?= e(url('/admin/categories')) ?>" class="form" novalidate>
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="field">
                        <label for="new-name">Name</label>
                        <input id="new-name" name="name" type="text" maxlength="100" required value="<?= e($old['name'] ?? '') ?>"<?= error_attrs($errors, 'new') ?>>
                        <?= field_error($errors, 'new') ?>
                    </div>
                    <div class="field">
                        <label for="new-sort">Order</label>
                        <input id="new-sort" name="sort_order" type="text" inputmode="numeric" value="<?= e($old['sort_order'] ?? '0') ?>">
                        <p class="field-hint">Smaller numbers appear first.</p>
                    </div>
                    <div class="field full">
                        <label for="new-desc">Description <span class="optional">(optional)</span></label>
                        <input id="new-desc" name="description" type="text" maxlength="255" value="<?= e($old['description'] ?? '') ?>">
                    </div>
                    <div class="field full checks">
                        <label class="check"><input type="checkbox" name="is_active" value="1" checked> Visible in the shop</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Add category</button>
                </div>
            </form>
        </div>

        <div class="panel">
            <h2>Your categories</h2>

            <?php if ($categories === []): ?>
                <p class="muted">No categories yet.</p>
            <?php else: ?>
                <ul class="cat-list">
                    <?php foreach ($categories as $category): ?>
                        <?php $cid = (int) $category['id']; $count = (int) $category['product_count']; ?>
                        <li class="cat-row">
                            <form method="post" action="<?= e(url('/admin/categories/' . $cid)) ?>" class="form cat-form" id="save-<?= e($cid) ?>" novalidate>
                                <?= csrf_field() ?>
                                <div class="field">
                                    <label for="name-<?= e($cid) ?>">Name</label>
                                    <input id="name-<?= e($cid) ?>" name="name" type="text" maxlength="100" required value="<?= e($category['name']) ?>"<?= error_attrs($errors, (string) $cid) ?>>
                                    <?= field_error($errors, (string) $cid) ?>
                                </div>
                                <div class="field">
                                    <label for="desc-<?= e($cid) ?>">Description</label>
                                    <input id="desc-<?= e($cid) ?>" name="description" type="text" maxlength="255" value="<?= e($category['description'] ?? '') ?>">
                                </div>
                                <div class="field field-narrow">
                                    <label for="sort-<?= e($cid) ?>">Order</label>
                                    <input id="sort-<?= e($cid) ?>" name="sort_order" type="text" inputmode="numeric" value="<?= e($category['sort_order']) ?>">
                                </div>
                                <div class="field field-check">
                                    <label class="check"><input type="checkbox" name="is_active" value="1"<?= (int) $category['is_active'] === 1 ? ' checked' : '' ?>> Visible</label>
                                </div>
                            </form>

                            <div class="cat-actions">
                                <span class="muted"><?= e($count) ?> <?= $count === 1 ? 'product' : 'products' ?></span>
                                <button class="btn btn-secondary btn-sm" type="submit" form="save-<?= e($cid) ?>">Save</button>
                                <form method="post" action="<?= e(url('/admin/categories/' . $cid . '/delete')) ?>" class="inline-form" data-confirm="Delete the category &quot;<?= e($category['name']) ?>&quot;?">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger btn-sm" type="submit"<?= $count > 0 ? ' disabled title="Move or delete its products first"' : '' ?>>Delete</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
