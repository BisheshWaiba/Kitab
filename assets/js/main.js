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
