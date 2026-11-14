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
