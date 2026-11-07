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
