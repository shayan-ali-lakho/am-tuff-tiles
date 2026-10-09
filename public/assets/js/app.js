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

// Mobile navigation: hamburger button opens and closes the menu panel
(function () {
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('site-nav');
    var header = document.querySelector('.site-header');

    if (!toggle || !nav) {
        return;
    }

    function isOpen() {
        return nav.classList.contains('is-open');
    }

    function setOpen(open) {
        nav.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    }

    toggle.addEventListener('click', function () {
        setOpen(!isOpen());
    });

    // Tapping a link, tapping outside the header, pressing Escape or rotating to a wide screen closes the menu
    nav.addEventListener('click', function (event) {
        if (event.target.closest && event.target.closest('a')) {
            setOpen(false);
        }
    });

    document.addEventListener('click', function (event) {
        if (isOpen() && header && !header.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isOpen()) {
            setOpen(false);
            toggle.focus();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 720 && isOpen()) {
            setOpen(false);
        }
    });
})();

// Header gets a soft shadow once the page is scrolled
(function () {
    var header = document.querySelector('.site-header');

    if (!header) {
        return;
    }

    function update() {
        header.classList.toggle('is-scrolled', (window.pageYOffset || document.documentElement.scrollTop) > 8);
    }

    update();
    window.addEventListener('scroll', update, { passive: true });
})();

// Blocks fade up as they scroll into view (skipped for people who prefer less motion)
(function () {
    if (!('IntersectionObserver' in window)) {
        return;
    }

    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    var selectors = [
        '.section-head',
        '.card-grid > li',
        '.product-grid > li',
        '.about-intro > *',
        '.about-points > li',
        '.about-cta',
        '.stat-grid > li',
        '.product-layout > *',
        '.cart-layout > *',
        '.panel',
        '.table-wrap',
        '.map-bar'
    ];

    var observer = new IntersectionObserver(function (entries) {
        var step = 0;

        entries.forEach(function (entry) {
            if (!entry.isIntersecting) {
                return;
            }

            // Items that appear together arrive one after another
            entry.target.style.setProperty('--reveal-delay', (Math.min(step, 8) * 70) + 'ms');
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
            step++;
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll(selectors.join(',')).forEach(function (element) {
        element.classList.add('reveal');
        observer.observe(element);
    });
})();

// Checkout: payment choice, EasyPaisa details, screenshot preview and double-click protection
(function () {
    var form = document.querySelector('[data-checkout-form]');

    if (!form) {
        return;
    }

    var panel = form.querySelector('[data-pay-panel]');
    var radios = form.querySelectorAll('input[name="payment_method"]');
    var button = form.querySelector('[data-submit-button]');
    var buttonText = button ? button.textContent : '';

    function syncPanel() {
        var checked = form.querySelector('input[name="payment_method"]:checked');
        var show = !!checked && checked.value === 'easypaisa';

        if (panel) {
            panel.classList.toggle('is-collapsed', !show);
        }
    }

    radios.forEach(function (radio) {
        radio.addEventListener('change', syncPanel);
    });
    syncPanel();

    // Copy the EasyPaisa number
    form.querySelectorAll('[data-copy]').forEach(function (copyButton) {
        copyButton.addEventListener('click', function () {
            var text = copyButton.getAttribute('data-copy') || '';
            var label = copyButton.textContent;

            function done() {
                copyButton.textContent = 'Copied';
                window.setTimeout(function () { copyButton.textContent = label; }, 1800);
            }

            function fallback() {
                var area = document.createElement('textarea');
                area.value = text;
                area.setAttribute('readonly', '');
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();

                try {
                    document.execCommand('copy');
                    done();
                } catch (error) {
                    // nothing else to do: the number is selectable on screen
                }

                document.body.removeChild(area);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done, fallback);
            } else {
                fallback();
            }
        });
    });

    // "Full amount" shortcut
    form.querySelectorAll('[data-fill-amount]').forEach(function (fillButton) {
        fillButton.addEventListener('click', function () {
            var input = document.getElementById('paid_amount');

            if (input) {
                input.value = fillButton.getAttribute('data-fill-amount');
                input.focus();
            }
        });
    });

    // Screenshot: show a small preview and warn about files that are too big
    var fileInput = document.getElementById('payment_screenshot');
    var preview = form.querySelector('[data-shot-preview]');
    var note = form.querySelector('[data-shot-note]');
    var maxBytes = 10 * 1024 * 1024;

    if (fileInput && preview) {
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            var image = preview.querySelector('img');

            preview.classList.remove('is-shown');
            if (note) { note.textContent = ''; }

            if (!file) {
                return;
            }

            if (file.size > maxBytes) {
                fileInput.value = '';
                if (note) { note.textContent = 'That file is larger than 10 MB. Please choose a smaller screenshot.'; }
                return;
            }

            if (!file.type || file.type.indexOf('image/') !== 0 || !window.FileReader || !image) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function () {
                image.src = reader.result;
                preview.classList.add('is-shown');
            };
            reader.readAsDataURL(file);
        });
    }

    // Placing an order twice by double-clicking (or tapping again on a slow upload) must not create two orders
    form.addEventListener('submit', function (event) {
        if (form.getAttribute('data-submitting') === '1') {
            event.preventDefault();
            return;
        }

        form.setAttribute('data-submitting', '1');

        if (button) {
            button.disabled = true;
            button.textContent = 'Placing your order...';
        }
    });

    // Coming back with the browser's back button must not leave the button stuck
    window.addEventListener('pageshow', function () {
        form.removeAttribute('data-submitting');

        if (button) {
            button.disabled = false;
            button.textContent = buttonText;
        }
    });
})();
