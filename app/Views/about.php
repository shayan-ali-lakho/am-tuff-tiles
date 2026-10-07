<section class="page-head">
    <div class="container">
        <h1>About <?= e(config('app.name')) ?></h1>
    </div>
</section>

<section class="section">
    <div class="container prose">
        <!-- Placeholder copy: replace with the real business story once the owner provides it. -->
        <p>
            <?= e(config('app.name')) ?> supplies tuff tiles, doors, garden products, metal gates,
            roof ceilings and other building products.
        </p>
        <p>
            Browse our range, place your order online and pay in cash on delivery.
        </p>
        <?php if (config('shop.email')): ?>
            <p>
                Questions? Email us at
                <a href="mailto:<?= e(config('shop.email')) ?>"><?= e(config('shop.email')) ?></a>.
            </p>
        <?php endif; ?>
    </div>
</section>
