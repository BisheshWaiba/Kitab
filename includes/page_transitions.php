<?php
// Page-to-page motion: opening a book makes its cover glide from the card to the book's page, and leaving the page glides
// it back to its card. It uses the browser's own page transitions (Chrome and Edge 126 and up). Other browsers never send
// the two events below, so they ignore all of this and load pages exactly as before.
//
// How it works: a transition only needs the SAME name on one element of the old page and one of the new page. A shelf of
// covers cannot all carry a name (a name may exist once per page), so the script names just one cover, on the one
// navigation at hand: the card that was clicked going out, the book page's cover coming in, and the reverse on the way back.
// Every navigation that is not about a book is skipped, so the rest of the site loads as it always did, and nothing runs for
// people who asked their system for less motion.
?>
    <style>
        @media (prefers-reduced-motion: no-preference) {
            @view-transition { navigation: auto; }
        }
        ::view-transition-group(book-cover) { animation-duration: .65s; animation-timing-function: var(--k-ease); }
        /* the photographed cover is cropped to its edge, so its own shadow is left behind: the travelling one brings a soft shadow of its own */
        ::view-transition-image-pair(book-cover) { filter: drop-shadow(0 16px 18px rgba(17, 18, 20, .35)); }
        /* a cover that has no partner on the other page (no card to land on) just fades away quickly */
        ::view-transition-old(book-cover):only-child,
        ::view-transition-new(book-cover):only-child { animation-duration: .25s; }
    </style>
    <script>
    (function () {
        if (!('onpageswap' in window) || !('onpagereveal' in window)) { return; }

        var NAME = 'book-cover';
        var COVER = 'img, .k-cover--empty';   // a cover is a picture, or the titled box shown when a book has none
        var CARD = '.hg-tile, .k-book, .gc-row, .gc-mini, .k-pick, article, li';
        var named = null;                      // the cover that carries the name right now
        var clicked = null;                    // the book link that was just clicked: { id, cover }

        // 17 for .../frontend/product.php?id=17, and 0 for any other address
        function bookId(url) {
            try {
                var u = new URL(url, location.href);
                return /\/product\.php$/.test(u.pathname) ? (parseInt(u.searchParams.get('id'), 10) || 0) : 0;
            } catch (e) { return 0; }
        }
        var here = bookId(location.href);

        // every cover on the page (or inside one card) that opens book number id
        function coversOf(id, root) {
            var found = [];
            var links = root.querySelectorAll('a[href*="product.php"]');
            for (var i = 0; i < links.length; i++) {
                var cover = bookId(links[i].href) === id ? links[i].querySelector(COVER) : null;
                if (cover) { found.push(cover); }
            }
            return found;
        }
        // the one to use: the first that is on screen, else the first that is shown at all
        function pick(list) {
            var shown = null;
            for (var i = 0; i < list.length; i++) {
                var r = list[i].getBoundingClientRect();
                if (r.width === 0 || r.height === 0) { continue; }
                if (r.bottom > 0 && r.top < innerHeight && r.right > 0 && r.left < innerWidth) { return list[i]; }
                shown = shown || list[i];
            }
            return shown;
        }
        function bookPageCover() { return document.querySelector('.gp-stage__cover'); }

        function release() {
            if (named) { named.style.viewTransitionName = ''; named = null; }
        }
        function give(cover) {
            release();
            if (cover.tagName === 'IMG') { cover.loading = 'eager'; } // a card further down must have its picture before it is photographed
            cover.style.viewTransitionName = NAME;
            named = cover;
        }

        // Which cover was clicked. Clicking the title or the button of a card counts too: they live in the same card.
        document.addEventListener('click', function (e) {
            clicked = null;
            var link = e.target.closest ? e.target.closest('a[href*="product.php"]') : null;
            var id = link ? bookId(link.href) : 0;
            if (!id) { return; }
            var cover = link.querySelector(COVER);
            if (!cover) {
                var card = link.closest(CARD);
                cover = card ? pick(coversOf(id, card)) : null;
            }
            if (cover) { clicked = { id: id, cover: cover }; }
        }, true);

        // Leaving this page: name the cover that is about to travel (it is photographed right after this runs)
        window.addEventListener('pageswap', function (e) {
            var transition = e.viewTransition;
            if (!transition) { return; }
            var to = e.activation && e.activation.entry ? bookId(e.activation.entry.url) : 0;
            var cover = null;
            if (to && to !== here) {
                cover = clicked && clicked.id === to ? clicked.cover : pick(coversOf(to, document)); // a card -> its book page
            } else if (here && !to) {
                cover = bookPageCover();                                                            // a book page -> back to a list
            }
            if (cover) { give(cover); } else { transition.skipTransition(); }
        });

        // Arriving on this page: name the cover the travelling one lands on
        window.addEventListener('pagereveal', function (e) {
            var transition = e.viewTransition;
            if (!transition) { return; }
            var nav = window.navigation;
            var from = nav && nav.activation && nav.activation.from ? bookId(nav.activation.from.url) : 0;
            var cover = null;
            if (here && from !== here) {
                cover = bookPageCover();                         // a book page opens: its cover is where the card's cover is going
            } else if (!here && from) {
                cover = pick(coversOf(from, document));          // a list opens again: the cover lands on the card it came from
            }
            if (cover) {
                give(cover);
                transition.finished.then(release, release);
            } else {
                transition.skipTransition();
            }
        });

        // Coming back from the browser's page cache must not find an old name still on a cover
        window.addEventListener('pageshow', function (e) { if (e.persisted) { release(); } });
    })();
    </script>
