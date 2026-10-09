// Pop-up messages (modals): the server-rendered result message, "are you sure?" questions and the "placing your order" box
var AMModal = (function () {
    var active = null; // { root, onClose, previous }
    var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

    function reduced() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function close(result) {
        if (!active) {
            return;
        }

        var current = active;
        active = null;
        document.body.classList.remove('modal-open');
        document.removeEventListener('keydown', current.keydown, true);

        function finish() {
            if (current.root.parentNode) {
                current.root.parentNode.removeChild(current.root);
            }

            if (current.previous && current.previous.focus) {
                try { current.previous.focus(); } catch (error) { /* element gone */ }
            }

            if (current.onClose) {
                current.onClose(result);
            }
        }

        if (reduced()) {
            finish();
        } else {
            current.root.classList.add('is-leaving');
            window.setTimeout(finish, 200);
        }
    }

    // Make a modal element work: focus, Tab trap, Esc, backdrop click, close buttons, auto close
    function enhance(root, options) {
        options = options || {};

        if (active) {
            close('replaced');
        }

        var box = root.querySelector('.modal-box');
        var current = { root: root, onClose: options.onClose, previous: document.activeElement };
        var dismissible = options.dismissible !== false;

        current.keydown = function (event) {
            if (event.key === 'Escape' && dismissible) {
                event.preventDefault();
                close('dismiss');
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            var items = Array.prototype.filter.call(root.querySelectorAll(FOCUSABLE), function (el) { return el.offsetParent !== null; });

            if (items.length === 0) {
                event.preventDefault();
                box.focus();
                return;
            }

            var first = items[0];
            var last = items[items.length - 1];

            if (event.shiftKey && (document.activeElement === first || document.activeElement === box)) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        active = current;
        document.body.classList.add('modal-open');
        document.addEventListener('keydown', current.keydown, true);

        root.addEventListener('click', function (event) {
            var target = event.target.closest ? event.target.closest('[data-modal-close], [data-modal-value]') : null;

            if (!target || !root.contains(target)) {
                return;
            }

            if (target.hasAttribute('data-modal-value')) {
                close(target.getAttribute('data-modal-value'));
            } else if (dismissible || target.tagName === 'BUTTON') {
                close('dismiss');
            }
        });

        // Success messages without buttons close by themselves (pausing while the mouse is over them)
        var autoClose = parseInt(root.getAttribute('data-autoclose') || '0', 10);

        if (autoClose > 0) {
            var left = autoClose;
            var started = Date.now();
            var timer = null;

            root.style.setProperty('--modal-time', autoClose + 'ms');

            var start = function () {
                started = Date.now();
                timer = window.setTimeout(function () { close('auto'); }, left);
                root.classList.remove('is-paused');
            };
            var stop = function () {
                if (timer === null) { return; }
                window.clearTimeout(timer);
                timer = null;
                left = Math.max(800, left - (Date.now() - started));
                root.classList.add('is-paused');
            };

            box.addEventListener('mouseenter', stop);
            box.addEventListener('mouseleave', start);
            start();
        }

        var preferred = root.querySelector('.btn-primary, .btn-danger') || root.querySelector('.btn');

        window.setTimeout(function () { (preferred || box).focus(); }, 30);
        return current;
    }

    function esc(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    var ICONS = {
        success: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        error: '<path d="M6 6l12 12M18 6L6 18"/>',
        warning: '<path d="M12 7v6"/><path d="M12 17.2v.01"/>',
        info: '<path d="M12 11v6"/><path d="M12 7.2v.01"/>'
    };

    // Build a modal from scratch. options: type, title, message, actions [{label, value, primary, danger}], loading, dismissible, onClose
    function open(options) {
        var type = options.type || 'info';
        var root = document.createElement('div');
        var actions = (options.actions || []).map(function (action) {
            var cls = action.danger ? 'btn btn-danger' : (action.primary ? 'btn btn-primary' : 'btn btn-secondary');
            return '<button type="button" class="' + cls + '" data-modal-value="' + esc(action.value) + '">' + esc(action.label) + '</button>';
        }).join('');

        root.className = 'modal is-' + type + (options.loading ? ' modal-loading' : '');
        root.setAttribute('data-modal', '');
        root.setAttribute('role', options.loading ? 'alert' : 'alertdialog');
        root.setAttribute('aria-modal', 'true');
        root.setAttribute('aria-labelledby', 'modal-title');
        root.innerHTML =
            '<div class="modal-backdrop"' + (options.dismissible === false ? '' : ' data-modal-close') + '></div>' +
            '<div class="modal-box" tabindex="-1">' +
            '<div class="modal-icon">' + (options.loading
                ? '<div class="modal-spinner" aria-hidden="true"></div>'
                : '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONS[type] || ICONS.info) + '</svg>') + '</div>' +
            '<h2 id="modal-title" class="modal-title">' + esc(options.title) + '</h2>' +
            (options.message ? '<p id="modal-text" class="modal-text">' + esc(options.message) + '</p>' : '') +
            '<div class="modal-actions">' + actions + '</div>' +
            '</div>';

        document.body.appendChild(root);

        return enhance(root, { onClose: options.onClose, dismissible: options.dismissible });
    }

    // "Are you sure?" - calls back only when the person chooses the main button
    function confirm(options, onYes) {
        open({
            type: options.danger ? 'warning' : 'info',
            title: options.title || 'Are you sure?',
            message: options.message,
            actions: [
                { label: options.cancel || 'Go back', value: 'no' },
                { label: options.ok || 'Yes, continue', value: 'yes', primary: !options.danger, danger: !!options.danger }
            ],
            onClose: function (result) {
                if (result === 'yes') {
                    onYes();
                }
            }
        });
    }

    // The message the server put on this page
    var server = document.querySelector('[data-modal]');

    if (server) {
        enhance(server);
    }

    return { open: open, confirm: confirm, close: close, isOpen: function () { return !!active; } };
})();

// Ask before destructive actions: <form data-confirm="Are you sure?"> (optional: data-confirm-title, data-confirm-ok)
document.addEventListener('submit', function (event) {
    var form = event.target;
    var message = form && form.getAttribute ? form.getAttribute('data-confirm') : null;

    if (!message) {
        return;
    }

    event.preventDefault();

    var danger = /delete|remove|cancel/i.test(message);

    AMModal.confirm({
        title: form.getAttribute('data-confirm-title') || (danger ? 'Please confirm' : 'Are you sure?'),
        message: message,
        ok: form.getAttribute('data-confirm-ok') || (danger ? 'Yes, do it' : 'Yes, continue'),
        danger: danger
    }, function () {
        // submit() skips the submit event, so the question is not asked twice
        HTMLFormElement.prototype.submit.call(form);
    });
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
    var loadingTimer = null;
    var waiting = null;

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

        // A box while the order (and the screenshot) is being sent; it cannot be closed by accident
        waiting = AMModal.open({
            type: 'info',
            title: 'Placing your order...',
            message: 'Please wait and do not close this page or press back.',
            loading: true,
            dismissible: false
        });

        loadingTimer = window.setTimeout(function () {
            var box = waiting && waiting.root ? waiting.root.querySelector('.modal-text') : null;

            if (box) {
                box.textContent = 'This is taking longer than usual (a big screenshot or slow internet). Please keep waiting. If nothing happens, call us before ordering again so you are not charged twice.';
            }
        }, 25000);
    });

    // Coming back with the browser's back button must not leave the button stuck
    window.addEventListener('pageshow', function () {
        form.removeAttribute('data-submitting');

        if (loadingTimer !== null) {
            window.clearTimeout(loadingTimer);
            loadingTimer = null;
        }

        if (waiting) {
            waiting = null;
            AMModal.close('dismiss');
        }

        if (button) {
            button.disabled = false;
            button.textContent = buttonText;
        }
    });
})();
