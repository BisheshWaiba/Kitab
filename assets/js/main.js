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

// Add to cart without leaving the page: the book goes in, the cart badge pops and a message slides in with "View cart".
// Without JavaScript the form posts normally and comes back to the book, which shows the same message once.
function bumpCartBadge(count) {
    var link = document.querySelector('.k-links a.k-cart:not(.k-cart--wish)');
    if (!link) {
        return;
    }
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

    form.addEventListener('submit', function (event) {
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
                    setTimeout(function () { label.textContent = normal; }, 1800);
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
