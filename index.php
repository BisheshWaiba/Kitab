<?php
require_once 'config/db.php';
require_once 'includes/helpers.php';
// The Home page is flat (white and light-grey surfaces, from the design canvas); only its buttons are glass.
// is-gbtn switches the glass buttons on, and both stylesheets are loaded by this page alone.
$body_class = 'is-gbtn'; // includes/header.php adds the Terracotta & Sand colour style (is-terra) to every page that has the glass buttons
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/home-flat.css'];
require_once 'includes/header.php';

// Hero: three covers (books that have one), fanned out
$hero_books = $conn->query("SELECT id, title, price, image FROM books WHERE image IS NOT NULL AND image <> '' AND seller_id IS NULL ORDER BY id ASC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

// Real numbers for the hero: the shop's average review rating, and the featured book (the middle cover)
$review_total = 0;
$review_sum = 0.0;
foreach (k_card_ratings() as [$n, $avg]) {
    $review_total += $n;
    $review_sum += $n * $avg;
}
$review_avg = $review_total > 0 ? $review_sum / $review_total : 0.0;
$featured = $hero_books[0] ?? null;

// Best Sellers (random)
$result_best = $conn->query("SELECT * FROM books WHERE seller_id IS NULL ORDER BY RAND() LIMIT 5");

// New Arrivals (latest)
$result_new = $conn->query("SELECT * FROM books WHERE seller_id IS NULL ORDER BY created_at DESC LIMIT 4");

// Fetch Curated Categories
$curated_cats = ['Arts & Photography', 'Travel', 'Nature'];
$curated_data = [];
foreach ($curated_cats as $cat) {
    $sql_curated = "SELECT * FROM books WHERE category = ? AND seller_id IS NULL ORDER BY RAND() LIMIT 4";
    $stmt = $conn->prepare($sql_curated);
    $stmt->bind_param("s", $cat);
    $stmt->execute();
    $curated_data[$cat] = $stmt->get_result();
}

// Collections: each genre with a small line icon (24x24 paths)
$genres = [
    'Fiction'   => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
    'Business'  => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>',
    'Tech'      => '<rect width="16" height="16" x="4" y="4" rx="2"/><rect width="6" height="6" x="9" y="9" rx="1"/><path d="M15 2v2M15 20v2M2 15h2M2 9h2M20 15h2M20 9h2M9 2v2M9 20v2"/>',
    'Science'   => '<path d="M10 2v7.527a2 2 0 0 1-.211.896L4.72 20.55a1 1 0 0 0 .9 1.45h12.76a1 1 0 0 0 .9-1.45l-5.069-10.127A2 2 0 0 1 14 9.527V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/>',
    'History'   => '<path d="M3 22h18"/><path d="M6 18v-7M10 18v-7M14 18v-7M18 18v-7"/><path d="M12 2l8 5H4z"/>',
    'Biography' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'Self-Help' => '<path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/>',
    'Mystery'   => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    'Romance'   => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
    'Fantasy'   => '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4M22 5h-4M4 17v2M5 18H3"/>',
];

// Each collection pill gets its own colour: an oklch hue angle (home-flat.css turns it into the tint, the icon colour and the hover fill)
$genre_hues = ['Fiction' => 275, 'Business' => 200, 'Tech' => 245, 'Science' => 150, 'History' => 70, 'Biography' => 35, 'Self-Help' => 115, 'Mystery' => 305, 'Romance' => 10, 'Fantasy' => 335];

// Which hero cover takes which spot in the fan (left, middle and right): index in $hero_books
$fan_order = [2, 0, 1];
?>

<main class="fl">

    <!-- Hero -->
    <section class="fh-top">
        <div class="fl-wrap fh-hero">
            <div class="fh-hero__copy">
                <h1 class="fl-h1">Discover Your Next <em>Favorite Book</em></h1>
                <p class="fl-lede">Fiction, business, history, art and more, brought to your door.</p>
            </div>

            <?php // The stage: a golden disc, the covers fanned out in front of it, and two glass cards with real numbers (the shop's average rating, the featured book). ?>
            <div class="fh-stage" data-hero-stage>
                <div class="fh-fan">
                    <?php foreach ($fan_order as $i): ?>
                        <?php if (isset($hero_books[$i])): $b = $hero_books[$i]; ?>
                            <a href="<?php echo BASE_URL; ?>frontend/product.php?id=<?php echo (int) $b['id']; ?>"
                                aria-label="<?php echo htmlspecialchars($b['title']); ?>">
                                <?php k_cover($b, 'fh-cover', false); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($review_total > 0): ?>
                    <div class="fh-chip fh-chip--rating" role="note">
                        <span class="k-stars" style="--pct:<?php echo round($review_avg / 5 * 100, 1); ?>%" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                        <span class="fh-chip__text"><span class="fh-chip__line"><strong><?php echo number_format($review_avg, 1); ?></strong> average</span> <small>from <?php echo $review_total; ?> reader reviews</small></span>
                    </div>
                <?php endif; ?>
                <?php if ($featured): ?>
                    <a class="fh-chip fh-chip--book" href="<?php echo BASE_URL; ?>frontend/product.php?id=<?php echo (int) $featured['id']; ?>">
                        <span class="fh-chip__text">
                            <small>Featured</small>
                            <strong><?php echo htmlspecialchars($featured['title']); ?></strong>
                            <span>Rs. <?php echo number_format((float) $featured['price']); ?></span>
                        </span>
                        <span class="fh-chip__go" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                        </span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Collections -->
    <section class="fl-sec fl-sec--band fh-colls" aria-labelledby="fh-collections">
        <div class="fl-wrap">
            <div class="fl-head">
                <div>
                    <h2 class="fl-h2" id="fh-collections">Browse by <em>collection</em></h2>
                    <p class="fl-sub">Start from what you love to read.</p>
                </div>
            </div>
        </div>

        <?php
        // Two endless rows of pills drifting in opposite directions (they stop under the pointer or keyboard focus). The second row has the same
        // genres in a different order. Each list is printed four times so the loop has no gap on a wide screen; the second row and the copies are
        // hidden from screen readers and keyboard tabbing (the first row already has every link).
        $genre_names = array_keys($genres);
        $marquee_rows = [$genre_names, array_merge(array_slice($genre_names, 5), array_slice($genre_names, 0, 5))];
        ?>
        <div class="fh-marquees">
            <?php foreach ($marquee_rows as $row => $names): ?>
                <div class="fh-marquee<?php echo $row ? ' fh-marquee--rev' : ''; ?>">
                    <ul class="fh-marquee__track">
                        <?php for ($copy = 0; $copy < 4; $copy++): ?>
                            <?php foreach ($names as $genre): $hidden = $row > 0 || $copy > 0; ?>
                                <li<?php echo $hidden ? ' aria-hidden="true"' : ''; ?>>
                                    <a class="k-genre fh-pill" style="--h:<?php echo (int) ($genre_hues[$genre] ?? 70); ?>" href="<?php echo BASE_URL; ?>frontend/shop.php?category=<?php echo urlencode($genre); ?>"<?php echo $hidden ? ' tabindex="-1"' : ''; ?>>
                                        <span class="fh-pill__badge" aria-hidden="true">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><?php echo $genres[$genre]; ?></svg>
                                        </span>
                                        <span class="fh-pill__name"><?php echo htmlspecialchars($genre); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endfor; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- New Arrivals -->
    <section class="fl-sec" aria-labelledby="fh-new">
        <div class="fl-wrap">
            <div class="fl-head">
                <div>
                    <h2 class="fl-h2" id="fh-new">New Arrivals</h2>
                    <p class="fl-sub">Fresh from the press</p>
                </div>
                <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-more">View all books
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </a>
            </div>
            <div class="fl-grid">
                <?php while ($row = $result_new->fetch_assoc()): ?>
                    <?php k_book_card($row, 'primary'); ?>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Best Sellers -->
    <section class="fl-sec fl-sec--band" aria-labelledby="fh-best">
        <div class="fl-wrap fh-best">
            <div class="fh-best__copy">
                <h2 class="fl-h2" id="fh-best">Best Sellers</h2>
                <p class="fl-lede">Books everyone is reading right now.</p>
                <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-btn k-btn--primary">See all</a>
            </div>
            <div class="fh-best__row">
                <?php while ($row = $result_best->fetch_assoc()): ?>
                    <a class="fh-best__book" href="<?php echo BASE_URL; ?>frontend/product.php?id=<?php echo (int) $row['id']; ?>"
                        aria-label="<?php echo htmlspecialchars($row['title']); ?>, <?php echo k_rating_say((int) $row['id']); ?>, Rs. <?php echo number_format((float) $row['price']); ?>">
                        <?php k_cover($row, 'fh-cover'); ?>
                        <?php echo k_rating_row((int) $row['id']); ?>
                        <span class="fh-best__price">Rs. <?php echo number_format((float) $row['price']); ?></span>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Our Picks -->
    <section class="fl-sec" aria-labelledby="fh-picks">
        <div class="fl-wrap">
            <div class="fl-head">
                <div>
                    <h2 class="fl-h2" id="fh-picks">Our picks for you</h2>
                    <p class="fl-sub">We have curated special book collection for you</p>
                </div>
                <div class="fl-chips">
                    <?php $first = true;
                    foreach ($curated_cats as $cat): ?>
                        <button type="button" class="k-chip<?php echo $first ? ' is-active' : ''; ?>"
                            data-target="picks-<?php echo trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($cat)), '-'); ?>"
                            aria-pressed="<?php echo $first ? 'true' : 'false'; ?>">
                            <?php echo htmlspecialchars($cat); ?>
                        </button>
                        <?php $first = false; endforeach; ?>
                </div>
            </div>
            <?php $first = true;
            foreach ($curated_data as $cat => $books): ?>
                <div id="picks-<?php echo trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($cat)), '-'); ?>"
                    class="k-picks__panel<?php echo $first ? ' is-active' : ''; ?>">
                    <?php if ($books->num_rows > 0): ?>
                        <?php while ($row = $books->fetch_assoc()): ?>
                            <?php k_book_card($row, 'primary'); ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="k-picks__empty">No books found in this curated category yet.</p>
                    <?php endif; ?>
                </div>
                <?php $first = false; endforeach; ?>
        </div>
    </section>

</main>

<?php require_once 'includes/footer.php'; ?>
