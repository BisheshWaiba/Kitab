<?php
// Dark mode, part 1 of 2: choose the theme before the page is painted, so a dark visitor never sees a flash of white.
// The choice lives on <html data-theme="light|dark"> and the styles key on it (see "Dark mode" in assets/css/kitab.css).
// Until the visitor presses the switch in the header the page follows the device, live; after that their choice is kept
// in this browser (localStorage "kitab-theme") and shared between open tabs. Part 2, the button, is in assets/js/main.js.
// Pages that do not include this file (the admin area) have no data-theme and stay light.
?>
    <script>
    (function () {
        var root = document.documentElement;
        var KEY = 'kitab-theme';
        var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        function saved() {
            try { var v = localStorage.getItem(KEY); return v === 'dark' || v === 'light' ? v : null; } catch (e) { return null; }
        }
        function current() { return saved() || (media && media.matches ? 'dark' : 'light'); }
        root.setAttribute('data-theme', current());
        if (media && media.addEventListener) {
            media.addEventListener('change', function () { if (!saved()) { root.setAttribute('data-theme', current()); } });
        }
        window.addEventListener('storage', function (e) { if (e.key === KEY) { root.setAttribute('data-theme', current()); } });
    })();
    </script>
    <noscript><style>.k-theme { display: none; }</style></noscript>
