<?php
/** Full-width Google Map shown just above the footer. Nothing is shown until a location is set. */
$src  = map_embed_url();
$link = map_link_url();
$address = (string) config('shop.address');

if ($src === '') {
    return;
}
?>
<section class="map-section" aria-label="Our location">
    <div class="map-bar container">
        <div>
            <h2>Find us</h2>
            <?php if ($address !== ''): ?><p><?= icon('pin') ?> <?= e($address) ?></p><?php endif; ?>
        </div>
        <?php if ($link !== ''): ?>
            <a class="btn btn-secondary btn-sm" href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>
        <?php endif; ?>
    </div>
    <iframe class="map-frame" src="<?= e($src) ?>" title="Map showing the AM Tuff Tiles location" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
</section>
