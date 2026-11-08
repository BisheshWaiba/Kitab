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
                var card = form.closest('.k-book');
                if (list && card && !data.wished) {
                    card.remove();
                    var total = document.querySelector('[data-wish-total]');
                    if (data.count === 0) {
                        window.location.reload();
                    } else if (total) {
                        total.textContent = data.count + (data.count === 1 ? ' book' : ' books') + ' saved for later';
                    }
                }
                button.disabled = false;
            })
            .catch(function () {
                // Session ended or the request failed: let the form do it the ordinary way
                form.submit();
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
