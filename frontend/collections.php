<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Flat page; only the buttons are glass: is-gbtn switches them on (see assets/css/glass-buttons.css)
$body_class = 'is-gbtn';
$hide_footer = true; // this page has no footer (the footer stays on Home, Shop, the Book page and Wishlist; see includes/footer.php)
$extra_css = ['assets/css/glass-buttons.css'];
$page_title = 'Collections';
require_once '../includes/header.php';

// One collection per category, built from the books themselves
$result = $conn->query(
    "SELECT category AS name, COUNT(*) AS books FROM books
     WHERE category IS NOT NULL AND category <> '' GROUP BY category ORDER BY category ASC"
);
$collections = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$total = count($collections);

// Up to three covers per collection, books that have a cover first
$shelves = [];
$result = $conn->query(
    "SELECT id, title, image, category FROM books WHERE category IS NOT NULL AND category <> ''
     ORDER BY (image IS NULL OR image = ''), id DESC"
);
while ($result && ($book = $result->fetch_assoc())) {
    $key = mb_strtolower($book['category']);
    if (count($shelves[$key] ?? []) < 3) {
        $shelves[$key][] = $book;
    }
}
?>

<section class="k-pagehead">
    <div class="k-pagehead__inner">
        <h1 class="k-h1">Collections</h1>
        <p><?php echo $total === 0 ? 'No collections yet' : 'Browse the shelves, ' . $total . ($total === 1 ? ' collection' : ' collections') . ' to explore'; ?></p>
    </div>
</section>

<div class="k-shop">
    <main class="k-shop__main">
        <?php if ($total > 0): ?>
            <ul class="k-collections">
                <?php foreach ($collections as $c): ?>
                    <li>
                        <a class="k-collection" href="shop.php?category=<?php echo urlencode($c['name']); ?>">
                            <div class="k-collection__shelf" aria-hidden="true">
                                <?php foreach ($shelves[mb_strtolower($c['name'])] ?? [] as $book) {
                                    k_cover($book, 'k-cover');
                                } ?>
                            </div>
                            <span class="k-collection__name"><?php echo htmlspecialchars($c['name']); ?></span>
                            <span class="k-collection__count"><?php echo (int) $c['books'] . ((int) $c['books'] === 1 ? ' book' : ' books'); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <?php k_empty_state('cart', 'No collections yet', 'Collections will appear here as soon as books are added to the store.', 'Back to Home', BASE_URL . 'index.php'); ?>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
