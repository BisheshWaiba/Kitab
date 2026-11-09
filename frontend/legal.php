<?php
// One template for every legal page. The wording lives in includes/legal_pages.php; open it as legal.php?page=privacy
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Flat page; only the buttons are glass: is-gbtn switches them on (see assets/css/glass-buttons.css)
$body_class = 'is-gbtn';
$hide_footer = true; // this page has no footer (the footer stays on Home, Shop, the Book page and Wishlist; see includes/footer.php)
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/pages-flat.css'];

$legal = require '../includes/legal_pages.php';
$site = $legal['site'];
$slug = is_string($_GET['page'] ?? null) ? $_GET['page'] : '';
$doc = $legal['pages'][$slug] ?? null;

// Escapes the wording, then fills in the tokens ({email} becomes a mail link)
$say = function (string $text) use ($site): string {
    $email = htmlspecialchars($site['email']);
    return strtr(htmlspecialchars($text), [
        '{site}' => htmlspecialchars($site['name']),
        '{email}' => '<a href="mailto:' . $email . '">' . $email . '</a>',
        '{address}' => htmlspecialchars($site['address']),
        '{law}' => htmlspecialchars($site['law']),
        '{return_days}' => (int) $site['return_days'],
        '{refund_days}' => (int) $site['refund_days'],
    ]);
};

if (!$doc) {
    http_response_code(404);
}
$page_title = $doc ? $doc['title'] : 'Page not found';
require_once '../includes/header.php';
?>

<section class="k-pagehead">
    <div class="k-pagehead__inner">
        <h1 class="k-h1"><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if ($doc): ?>
            <p>Last updated <?php echo date('F j, Y', strtotime($site['updated'])); ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="k-shop">
    <main class="k-shop__main">
        <nav aria-label="Legal pages">
            <ul class="k-cats">
                <?php foreach ($legal['pages'] as $key => $page): ?>
                    <li><a href="legal.php?page=<?php echo urlencode($key); ?>"
                            class="k-genre<?php echo $key === $slug ? ' is-active' : ''; ?>"
                            <?php echo $key === $slug ? 'aria-current="page"' : ''; ?>><?php echo htmlspecialchars($page['title']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <?php if ($doc): ?>
            <article class="k-prose">
                <p class="k-prose__lead"><?php echo $say($doc['lead']); ?></p>

                <?php foreach ($doc['sections'] as $section): ?>
                    <h2><?php echo $say($section['h']); ?></h2>
                    <?php foreach ($section['p'] ?? [] as $text): ?>
                        <p><?php echo $say($text); ?></p>
                    <?php endforeach; ?>
                    <?php foreach (['ul', 'ol'] as $list): ?>
                        <?php if (!empty($section[$list])): ?>
                            <<?php echo $list; ?>>
                                <?php foreach ($section[$list] as $text): ?>
                                    <li><?php echo $say($text); ?></li>
                                <?php endforeach; ?>
                            </<?php echo $list; ?>>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php foreach ($section['after'] ?? [] as $text): ?>
                        <p><?php echo $say($text); ?></p>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <p class="k-prose__contact">Questions about this page? Write to us at <?php echo $say('{email}'); ?>.</p>
            </article>
        <?php else: ?>
            <?php k_empty_state('cart', 'Page not found', "We couldn't find that page. It may have moved, or the link is mistyped.", 'Back to Home', BASE_URL . 'index.php'); ?>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
