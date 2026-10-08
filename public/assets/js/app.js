// Ask before destructive actions: <form data-confirm="Are you sure?">
document.addEventListener('submit', function (event) {
    var message = event.target && event.target.getAttribute ? event.target.getAttribute('data-confirm') : null;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

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
