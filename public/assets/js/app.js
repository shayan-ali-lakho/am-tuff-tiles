// Ask before destructive actions: <form data-confirm="Are you sure?">
document.addEventListener('submit', function (event) {
    var message = event.target && event.target.getAttribute ? event.target.getAttribute('data-confirm') : null;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

// Shop: show or hide the filter panel on small screens
(function () {
    var toggle = document.querySelector('.filter-toggle');
    var panel = document.getElementById('filter-panel');

    if (!toggle || !panel) {
        return;
    }

    toggle.addEventListener('click', function () {
        var open = panel.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
})();

// Product page: clicking a small photo shows it large (without JavaScript the thumbnails simply open the photo)
(function () {
    document.querySelectorAll('[data-gallery]').forEach(function (gallery) {
        var main = gallery.querySelector('[data-gallery-main]');
        var mainLink = gallery.querySelector('.gallery-main');
        var thumbs = gallery.querySelectorAll('[data-gallery-thumb]');

        if (!main || !mainLink) {
            return;
        }

        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function (event) {
                event.preventDefault();
                main.src = thumb.getAttribute('data-full');
                mainLink.href = thumb.getAttribute('data-full');

                thumbs.forEach(function (other) { other.classList.remove('is-active'); });
                thumb.classList.add('is-active');
            });
        });
    });
})();

// Mobile navigation toggle
(function () {
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('site-nav');

    if (!toggle || !nav) {
        return;
    }

    toggle.addEventListener('click', function () {
        var open = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
})();
