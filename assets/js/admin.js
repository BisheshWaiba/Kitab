// Admin area: on phones and tablets the menu opens from the button in the top bar (the sidebar itself is the menu).
// Without this script the sidebar simply stays open above the page (assets/css/admin-flat.css only hides it under .has-js).
(function () {
    var button = document.querySelector('[data-admin-menu]');
    var side = document.getElementById('k-adminside');
    if (!button || !side) {
        return;
    }
    function set(open) {
        side.classList.toggle('is-open', open);
        document.body.classList.toggle('has-adminmenu', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    button.addEventListener('click', function () {
        set(!side.classList.contains('is-open'));
    });
    // a tap on the dimmed page, or Escape, closes it
    document.addEventListener('click', function (e) {
        if (side.classList.contains('is-open') && !e.target.closest('#k-adminside, [data-admin-menu]')) {
            set(false);
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && side.classList.contains('is-open')) {
            set(false);
            button.focus();
        }
    });
    // widening the window past the phone layout closes it; so does coming back with the Back button
    if (window.matchMedia) {
        var wide = window.matchMedia('(min-width: 901px)');
        if (wide.addEventListener) {
            wide.addEventListener('change', function (e) { if (e.matches) { set(false); } });
        }
    }
    window.addEventListener('pageshow', function () { set(false); });
})();

// "Are you sure?": a form with data-confirm="..." (and optionally data-confirm-title / data-confirm-button) asks in a dialog of our
// own before it is sent, instead of the browser's plain confirm() box. Cancel, Escape or a click outside closes it and sends nothing.
(function () {
    if (!window.HTMLDialogElement) {
        // an old browser without <dialog>: fall back to the plain box
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (form.hasAttribute && form.hasAttribute('data-confirm') && !window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        }, true);
        return;
    }
    var dialog = null;
    var title, text, ok, cancel;
    var pending = null;     // the form waiting for an answer
    var opener = null;      // what had focus, so it gets it back

    function build() {
        dialog = document.createElement('dialog');
        dialog.className = 'k-dialog';
        dialog.setAttribute('aria-labelledby', 'k-dialog-title');
        dialog.innerHTML = '<h2 class="k-dialog__title" id="k-dialog-title"></h2><p class="k-dialog__text"></p>' +
            '<div class="k-dialog__actions"><button type="button" class="k-btn k-btn--outline" data-dialog-cancel>Cancel</button>' +
            '<button type="button" class="k-btn k-btn--danger-light" data-dialog-ok></button></div>';
        document.body.appendChild(dialog);
        title = dialog.querySelector('.k-dialog__title');
        text = dialog.querySelector('.k-dialog__text');
        ok = dialog.querySelector('[data-dialog-ok]');
        cancel = dialog.querySelector('[data-dialog-cancel]');
        cancel.addEventListener('click', function () { dialog.close(); });
        ok.addEventListener('click', function () {
            var form = pending;
            pending = null;
            dialog.close();
            if (form) { form.submit(); } // submit() skips this handler, so it is not asked twice
        });
        // a click on the dimmed page (the dialog's own backdrop) closes it too
        dialog.addEventListener('click', function (e) { if (e.target === dialog) { dialog.close(); } });
        dialog.addEventListener('close', function () {
            pending = null;
            if (opener && opener.focus) { opener.focus(); }
        });
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.hasAttribute || !form.hasAttribute('data-confirm')) {
            return;
        }
        e.preventDefault();
        if (!dialog) { build(); }
        pending = form;
        opener = document.activeElement;
        title.textContent = form.getAttribute('data-confirm-title') || 'Are you sure?';
        text.textContent = form.getAttribute('data-confirm');
        ok.textContent = form.getAttribute('data-confirm-button') || 'Delete';
        dialog.showModal();
        cancel.focus(); // the safe choice has the focus, so an accidental Enter does not delete
    });
})();
