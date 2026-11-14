// Shop: on phones the category chips scroll sideways, so bring the chosen one into view
function showActiveCategory() {
    var activeCategory = document.querySelector('.k-cats .is-active');
    if (activeCategory && activeCategory.scrollIntoView) {
        activeCategory.scrollIntoView({ block: 'nearest', inline: 'center' });
    }
}
if (document.querySelector('.k-cats')) {
    // Run again once the page and web fonts have settled, because the chips change width when the font loads
    window.addEventListener('load', showActiveCategory);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(showActiveCategory);
    }
}

// Image pickers (profile setup, sell a book): choose button with live preview
document.querySelectorAll('[data-upload]').forEach(function (box) {
    var input = box.querySelector('input[type="file"]');
    var pick = box.querySelector('[data-upload-pick]');
    var preview = box.querySelector('[data-upload-preview]');
    var img = preview.querySelector('img');

    pick.addEventListener('click', function () {
        input.click();
    });
    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
            img.src = e.target.result;
            preview.classList.add('has-image');
        };
        reader.readAsDataURL(file);
    });
});

// Wishlist hearts: save or remove a book without reloading the page.
// Without JavaScript the same form posts normally and comes back to the page.
function setWishCount(count) {
    var link = document.querySelector('.k-cart--wish');
    if (!link) {
        return;
    }
    var badge = link.querySelector('.k-cart__badge');
    if (count > 0 && !badge) {
        badge = document.createElement('span');
        badge.className = 'k-cart__badge';
        link.appendChild(badge);
    }
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
        } else {
            badge.remove();
        }
    }
    link.setAttribute('aria-label', count > 0 ? 'Wishlist, ' + count + ' saved' : 'Wishlist');
}

document.querySelectorAll('form[data-wish]').forEach(function (form) {
    var button = form.querySelector('[data-wish-btn]');
    var action = form.querySelector('input[name="do"]');

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (button.disabled) {
            return;
        }
        button.disabled = true;
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (response) {
                return response.ok ? response.json() : Promise.reject();
            })
            .then(function (data) {
                action.value = data.wished ? 'remove' : 'add';
                button.classList.toggle('is-on', data.wished);
                if (button.hasAttribute('aria-pressed')) {
                    button.setAttribute('aria-pressed', data.wished ? 'true' : 'false');
                }
                var text = button.querySelector('[data-wish-text]');
                if (text) {
                    text.textContent = data.wished ? text.dataset.on : text.dataset.off;
                }
                setWishCount(data.count);

                // On the wishlist page a removed book leaves the list
                var list = document.querySelector('[data-wish-list]');
                var card = form.closest('.k-book, .hg-tile');
                if (list && card && !data.wished) {
                    // the card fades out first, then leaves the list (straight away for people who asked for less motion)
                    card.classList.add('is-leaving');
                    var wait = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 280;
                    setTimeout(function () {
                        card.remove();
                        var total = document.querySelector('[data-wish-total]');
                        if (data.count === 0) {
                            window.location.reload();
                        } else if (total) {
                            total.textContent = data.count + (data.count === 1 ? ' book' : ' books') + ' saved for later';
                        }
                    }, wait);
                }
                button.disabled = false;
            })
            .catch(function () {
                // Session ended or the request failed: let the form do it the ordinary way
                form.submit();
            });
    });
});

// Form errors: a form marked data-validate shows what is wrong as a short message under the field, not in the browser's
// own popup. The checks come from the fields' own rules (required, minlength, type="email", pattern, min and max), and
// these optional attributes on a field give the wording:
//   data-msg-missing  a required field left empty      data-msg-invalid  a value of the wrong shape (bad email, number, pattern)
//   data-match="id"   must be the same as the field with that id (data-msg-match is the message)
// Without JavaScript the browser's own checks still run, and the server checks everything again either way.
(function () {
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // the same rule PHP's email check applies: something@something.something

    function group(el) {
        return Array.prototype.filter.call(el.form.querySelectorAll('input[type="radio"]'), function (r) { return r.name === el.name; });
    }
    function targets(el) { return el.type === 'radio' ? group(el) : [el]; }
    function messageId(el) { return (el.type === 'radio' ? el.name : (el.id || el.name)) + '-error'; }
    function holder(el) {
        return (el.type === 'radio' && el.closest('fieldset')) || el.closest('.k-field') || el.parentNode;
    }
    function invalid(el) {
        return el.dataset.msgInvalid || (el.type === 'email' ? 'Enter a valid email address, like name@example.com.'
            : el.type === 'number' ? 'Enter a valid number.' : 'Please check this value.');
    }

    // What is wrong with this field, as a sentence ('' when nothing is)
    function problem(el) {
        var v = el.validity;
        if (el.type === 'radio') {
            return !el.required || group(el).some(function (r) { return r.checked; }) ? '' : (el.dataset.msgMissing || 'Please choose an option.');
        }
        if (el.type === 'checkbox') {
            return el.required && !el.checked ? (el.dataset.msgMissing || 'Please tick this box to continue.') : '';
        }
        var text = el.type === 'password' ? el.value : el.value.trim(); // spaces alone do not count as an answer
        if (el.required && text === '') {
            return el.dataset.msgMissing || (el.tagName === 'SELECT' ? 'Please choose an option.' : 'Please fill in this field.');
        }
        if (v.badInput) { return invalid(el); }
        if (text === '') { return ''; }
        if (el.dataset.match) {
            var other = document.getElementById(el.dataset.match);
            if (other && other.value !== el.value) { return el.dataset.msgMatch || 'These do not match.'; }
        }
        // Counted here, because browsers only apply minlength and maxlength to text that was typed (not to autofill)
        if (el.minLength > 0 && text.length < el.minLength) { return 'Use at least ' + el.minLength + ' characters (you typed ' + text.length + ').'; }
        if (el.maxLength >= 0 && el.value.length > el.maxLength) { return 'Use ' + el.maxLength + ' characters or fewer.'; }
        if (el.type === 'email' && !EMAIL.test(text)) { return invalid(el); }
        if (v.typeMismatch || v.patternMismatch || v.rangeUnderflow || v.rangeOverflow || v.stepMismatch) { return invalid(el); }
        return '';
    }

    function describedBy(field, id, on) {
        var ids = (field.getAttribute('aria-describedby') || '').split(' ').filter(function (x) { return x && x !== id; });
        if (on) { ids.push(id); }
        if (ids.length) { field.setAttribute('aria-describedby', ids.join(' ')); } else { field.removeAttribute('aria-describedby'); }
    }
    function show(el, text) {
        var id = messageId(el);
        var box = document.getElementById(id); // the server may have printed this message already
        if (!box) {
            box = document.createElement('p');
            box.className = 'k-field__error';
            box.id = id;
            holder(el).appendChild(box);
        }
        box.textContent = text;
        targets(el).forEach(function (field) {
            field.setAttribute('aria-invalid', 'true');
            describedBy(field, id, true);
        });
    }
    function clear(el) {
        var id = messageId(el);
        var box = document.getElementById(id);
        if (box) { box.remove(); }
        targets(el).forEach(function (field) {
            field.removeAttribute('aria-invalid');
            describedBy(field, id, false);
        });
    }
    function check(el) {
        var text = problem(el);
        if (text) { show(el, text); } else { clear(el); }
        return text === '';
    }
    function reveal(el) {
        el.focus({ preventScroll: true });
        holder(el).scrollIntoView({ block: 'nearest', behavior: reduced ? 'auto' : 'smooth' });
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.setAttribute('novalidate', '');

        function fields() {
            var seen = {};
            return Array.prototype.filter.call(form.elements, function (el) {
                if (!el.willValidate || ['hidden', 'submit', 'button', 'reset', 'file'].indexOf(el.type) !== -1) { return false; }
                if (el.type === 'radio') {
                    if (seen[el.name]) { return false; }
                    seen[el.name] = true;
                }
                return true;
            });
        }

        form.addEventListener('submit', function (event) {
            var first = null;
            fields().forEach(function (el) {
                if (!check(el) && !first) { first = el; }
            });
            if (first) {
                event.preventDefault();
                reveal(first);
            }
        });

        // Once a message is showing, it follows what the visitor types and goes away the moment the field is fine
        function recheck(event) {
            var el = event.target;
            if (el.willValidate && el.getAttribute('aria-invalid') === 'true') {
                check(el);
            }
        }
        form.addEventListener('input', recheck);
        form.addEventListener('change', recheck);

        // The server sent the form back with messages in it: put the cursor on the first one
        var bad = form.querySelector('[aria-invalid="true"]');
        if (bad) { reveal(bad); }
    });
})();

// Dark mode switch. The page already carries data-theme (includes/theme.php ran before it was painted); this flips it,
// remembers the choice, and fades between the two where the browser can (not for people who asked for less motion).
document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    function show() {
        button.setAttribute('aria-pressed', root.getAttribute('data-theme') === 'dark' ? 'true' : 'false');
    }
    show();
    button.addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        function apply() {
            root.setAttribute('data-theme', next);
            show();
        }
        try { localStorage.setItem('kitab-theme', next); } catch (e) { /* private window: it still works until the page is left */ }
        var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (document.startViewTransition && !calm) {
            document.startViewTransition(apply);
        } else {
            apply();
        }
    });
    // the device or another tab changed the theme: keep the button's state in step
    if (media && media.addEventListener) { media.addEventListener('change', show); }
    window.addEventListener('storage', show);
});

// Phone header: the search and the menu open as panels under the bar, one at a time. The buttons only show on phones
// (assets/css/kitab.css, "Phone header"); without this script the header stays as it is on tablets.
(function () {
    var nav = document.querySelector('.k-nav');
    var buttons = nav ? nav.querySelectorAll('[data-nav-toggle]') : [];
    if (!buttons.length) {
        return;
    }
    function opened() {
        return nav.classList.contains('is-open-search') ? 'search' : (nav.classList.contains('is-open-menu') ? 'menu' : '');
    }
    function set(which, focusInput) {
        nav.classList.toggle('is-open-search', which === 'search');
        nav.classList.toggle('is-open-menu', which === 'menu');
        buttons.forEach(function (b) {
            b.setAttribute('aria-expanded', b.getAttribute('data-nav-toggle') === which ? 'true' : 'false');
        });
        var input = nav.querySelector('.k-search input');
        if (which === 'search' && focusInput && input) {
            input.focus();
        }
    }
    buttons.forEach(function (b) {
        b.addEventListener('click', function () {
            var which = b.getAttribute('data-nav-toggle');
            set(opened() === which ? '' : which, true);
        });
    });
    // a tap on the dimmed page (outside the bar and its panels) closes the panel, and so does Escape
    document.addEventListener('click', function (e) {
        if (opened() && !e.target.closest('.k-nav__inner')) {
            set('');
        }
    });
    document.addEventListener('keydown', function (e) {
        var which = opened();
        if (e.key === 'Escape' && which) {
            set('');
            var back = nav.querySelector('[data-nav-toggle="' + which + '"]');
            if (back) {
                back.focus();
            }
        }
    });
    // turning the phone sideways (or widening the window) leaves the phone layout: close whatever is open
    if (window.matchMedia) {
        var wide = window.matchMedia('(min-width: 641px)');
        var closeWhenWide = function (e) { if (e.matches) { set(''); } };
        if (wide.addEventListener) { wide.addEventListener('change', closeWhenWide); }
    }
    // coming back with the browser's Back button must not find a panel still open
    window.addEventListener('pageshow', function () { set(''); });
})();

// Shop controls: the sort applies at once, and empty price boxes (and the default sort) stay out of the address
document.querySelectorAll('form[data-shop-tools]').forEach(function (form) {
    var skipped = [];
    form.querySelectorAll('select[data-autosubmit]').forEach(function (select) {
        select.addEventListener('change', function () {
            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });
    // phones: the "Price" button shows or hides the price boxes (the button itself only shows on phones, see shop-flat.css)
    var filterButton = form.querySelector('[data-filter-toggle]');
    if (filterButton) {
        filterButton.addEventListener('click', function () {
            var open = form.classList.toggle('is-filters-open');
            filterButton.setAttribute('aria-expanded', open ? 'true' : 'false');
            var first = form.querySelector('.gs-price input');
            if (open && first) {
                first.focus();
            }
        });
    }
    form.addEventListener('submit', function (event) {
        if (event.defaultPrevented) {
            return; // a price box has a message under it: nothing is sent, so nothing is switched off
        }
        form.querySelectorAll('input[name="min"], input[name="max"]').forEach(function (input) {
            if (input.value.trim() === '') {
                input.disabled = true;
                skipped.push(input);
            }
        });
        var sort = form.querySelector('select[name="sort"]');
        if (sort && sort.value === 'newest') {
            sort.disabled = true;
            skipped.push(sort);
        }
    });
    // coming back with the browser's Back button must not find them still switched off
    window.addEventListener('pageshow', function () {
        skipped.forEach(function (el) { el.disabled = false; });
        skipped = [];
    });
});

// Add to cart without leaving the page: the book goes in, the cart badge pops and a message slides in with "View cart".
// Without JavaScript the form posts normally and comes back to the book, which shows the same message once.
function bumpCartBadge(count) {
    // two cart links in the header: the one beside the menu links (wide screens) and the one in the phone bar; only one shows at a time
    document.querySelectorAll('.k-nav a.k-cart:not(.k-cart--wish)').forEach(function (link) {
        var badge = link.querySelector('.k-cart__badge');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'k-cart__badge';
            link.appendChild(badge);
        }
        if (typeof count === 'number') {
            badge.textContent = count;
            link.setAttribute('aria-label', 'Cart, ' + count + (count === 1 ? ' item' : ' items'));
        }
        badge.classList.remove('k-cart__badge--bump');
        void badge.offsetWidth; // lets the animation start again when a book is added twice in a row
        badge.classList.add('k-cart__badge--bump');
    });
}

// Sets up a message that is already in the page (shown by the server or built just now): close button, auto-dismiss
function initToast(toast) {
    var timer;
    function close() {
        clearTimeout(timer);
        toast.classList.add('is-out');
        setTimeout(function () {
            toast.remove();
        }, 220);
    }
    function start() {
        clearTimeout(timer);
        timer = setTimeout(close, 6000);
    }
    var button = toast.querySelector('.gt-toast__close');
    if (button) {
        button.addEventListener('click', close);
    }
    toast.addEventListener('mouseenter', function () { clearTimeout(timer); });
    toast.addEventListener('focusin', function () { clearTimeout(timer); });
    toast.addEventListener('mouseleave', start);
    toast.addEventListener('focusout', start);
    start();
}

function showCartToast(title, cover, added) {
    var box = document.querySelector('.gt-toasts');
    if (!box) {
        box = document.createElement('div');
        box.className = 'gt-toasts';
        box.setAttribute('aria-live', 'polite');
        document.body.appendChild(box);
    }
    box.querySelectorAll('.gt-toast').forEach(function (old) { old.remove(); }); // one message at a time

    var toast = document.createElement('div');
    toast.className = 'gt-toast';
    toast.setAttribute('role', 'status');
    if (cover) {
        var img = document.createElement('img');
        img.className = 'gt-toast__img';
        img.src = cover;
        img.alt = '';
        toast.appendChild(img);
    }
    var text = document.createElement('div');
    text.className = 'gt-toast__text';
    var strong = document.createElement('strong');
    strong.textContent = 'Added to your cart';
    var line = document.createElement('span');
    line.textContent = title + ' × ' + added; // the title is plain text, never HTML
    text.appendChild(strong);
    text.appendChild(line);
    toast.appendChild(text);
    var view = document.createElement('a');
    view.className = 'k-btn k-btn--primary k-btn--sm';
    view.href = 'cart.php';
    view.textContent = 'View cart';
    toast.appendChild(view);
    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'gt-toast__close';
    close.setAttribute('aria-label', 'Dismiss');
    close.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>';
    toast.appendChild(close);
    box.appendChild(toast);
    initToast(toast);
}

document.querySelectorAll('.gt-toast').forEach(function (toast) {
    initToast(toast);
    if (toast.hasAttribute('data-bump')) {
        bumpCartBadge();
    }
});

document.querySelectorAll('form[data-add-form]').forEach(function (form) {
    var button = form.querySelector('button[name="add_to_cart"]');
    var label = button.querySelector('[data-add-label]');
    var normal = label ? label.textContent : '';
    var plain = false;
    var qty = form.querySelector('input[name="quantity"]');
    form.setAttribute('novalidate', ''); // a quantity typed out of range is put right below instead of shown in a browser popup

    form.addEventListener('submit', function (event) {
        if (qty) {
            var n = parseInt(qty.value, 10);
            qty.value = !n || n < 1 ? 1 : Math.min(n, parseInt(qty.max, 10) || 100);
        }
        if (plain) {
            return; // the script could not do it: let the form post the ordinary way
        }
        event.preventDefault();
        if (button.disabled) {
            return;
        }
        button.disabled = true;
        if (label) {
            label.textContent = 'Adding...';
        }
        var body = new FormData(form);
        body.append('add_to_cart', '1');
        fetch(form.action, { method: 'POST', body: body, headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (response) {
                return response.ok ? response.json() : Promise.reject();
            })
            .then(function (data) {
                bumpCartBadge(data.count);
                showCartToast(form.dataset.title, form.dataset.cover, data.added);
                if (label) {
                    label.textContent = 'Added';
                    button.classList.add('is-added'); // on phones the button has no words: this swaps the cart icon for a tick
                    setTimeout(function () { label.textContent = normal; button.classList.remove('is-added'); }, 1800);
                }
                button.disabled = false;
            })
            .catch(function () {
                plain = true;
                button.disabled = false;
                if (label) {
                    label.textContent = normal;
                }
                form.requestSubmit(button);
            });
    });
});

// Product page: quantity stepper
document.querySelectorAll('[data-qty]').forEach(function (box) {
    var input = box.querySelector('input');
    box.querySelectorAll('[data-step]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var min = parseInt(input.min, 10) || 1;
            var max = parseInt(input.max, 10) || 100;
            var next = (parseInt(input.value, 10) || min) + parseInt(btn.dataset.step, 10);
            input.value = Math.min(max, Math.max(min, next));
        });
    });
});

// Cart: changing a quantity updates the cart after a short pause, so a few quick clicks make one update.
// Without JavaScript the form has an "Update cart" button instead.
document.querySelectorAll('form[data-cart-form]').forEach(function (form) {
    var timer;
    function soon() {
        clearTimeout(timer);
        timer = setTimeout(function () {
            form.querySelectorAll('input[type="number"]').forEach(function (input) {
                var n = parseInt(input.value, 10);
                input.value = !n || n < 1 ? 1 : Math.min(n, 100); // an empty box must not remove the book
            });
            form.submit();
        }, 450);
    }
    form.querySelectorAll('[data-step]').forEach(function (btn) {
        btn.addEventListener('click', soon);
    });
    form.querySelectorAll('input[type="number"]').forEach(function (input) {
        input.addEventListener('change', soon);
    });
});

// Home page: "Our picks" category tabs
document.querySelectorAll('.k-chip[data-target]').forEach(function (chip) {
    chip.addEventListener('click', function () {
        document.querySelectorAll('.k-chip[data-target]').forEach(function (c) {
            c.classList.remove('is-active');
            c.setAttribute('aria-pressed', 'false');
        });
        document.querySelectorAll('.k-picks__panel').forEach(function (p) {
            p.classList.remove('is-active');
        });

        chip.classList.add('is-active');
        chip.setAttribute('aria-pressed', 'true');
        var panel = document.getElementById(chip.dataset.target);
        if (panel) {
            panel.classList.add('is-active');
        }
    });
});
