<?php
require_once 'config/db.php';
require_once 'includes/helpers.php';
// The Home page is flat (white and light-grey surfaces, from the design canvas); only its buttons are glass.
// is-gbtn switches the glass buttons on, and both stylesheets are loaded by this page alone.
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/home-flat.css'];
require_once 'includes/header.php';

// Hero: three covers (books that have one), fanned out
$hero_books = $conn->query("SELECT id, title, price, image FROM books WHERE image IS NOT NULL AND image <> '' ORDER BY id ASC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

// Real numbers for the hero: how many books and categories, the shop's average review rating, and the featured book (the middle cover)
[$book_total, $cat_total] = array_map('intval', $conn->query("SELECT COUNT(*), COUNT(DISTINCT NULLIF(category, '')) FROM books")->fetch_row());
$review_total = 0;
$review_sum = 0.0;
foreach (k_card_ratings() as [$n, $avg]) {
    $review_total += $n;
    $review_sum += $n * $avg;
}
$review_avg = $review_total > 0 ? $review_sum / $review_total : 0.0;
$featured = $hero_books[0] ?? null;

// Best Sellers (random)
$result_best = $conn->query("SELECT * FROM books ORDER BY RAND() LIMIT 5");

// New Arrivals (latest)
$result_new = $conn->query("SELECT * FROM books ORDER BY created_at DESC LIMIT 4");

// Fetch Curated Categories
$curated_cats = ['Arts & Photography', 'Travel', 'Nature'];
$curated_data = [];
foreach ($curated_cats as $cat) {
    $sql_curated = "SELECT * FROM books WHERE category = ? ORDER BY RAND() LIMIT 4";
    $stmt = $conn->prepare($sql_curated);
    $stmt->bind_param("s", $cat);
    $stmt->execute();
    $curated_data[$cat] = $stmt->get_result();
}

$genres = ['Fiction', 'Business', 'Tech', 'Science', 'History', 'Biography', 'Self-Help', 'Mystery', 'Romance', 'Fantasy'];

// Which hero cover takes which spot in the fan (left, middle and right): index in $hero_books
$fan_order = [2, 0, 1];
?>

<main class="fl">

    <!-- Hero -->
    <section class="fh-top">
        <div class="fl-wrap fh-hero">
            <div class="fh-hero__copy">
                <p class="fh-eyebrow"><span class="fh-eyebrow__dot" aria-hidden="true"></span><?php echo $book_total . ($book_total === 1 ? ' book' : ' books') . ' in ' . $cat_total . ($cat_total === 1 ? ' category' : ' categories'); ?></p>
                <h1 class="fl-h1">Discover Your Next <em>Favorite Book</em></h1>
                <p class="fl-lede">Fiction, business, history, art and more, brought to your door.</p>
                <div class="fh-hero__cta">
                    <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-btn k-btn--primary k-btn--lg">Shop Now
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" />
                        </svg>
                    </a>
                    <a href="<?php echo BASE_URL; ?>frontend/register.php" class="k-btn k-btn--outline k-btn--lg">Join KITAB</a>
                </div>
                <div class="fh-pay">
                    <span class="fh-pay__icon" aria-hidden="true">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="6" width="19" height="12" rx="2.2" /><circle cx="12" cy="12" r="2.8" /><path d="M6 9.5v.01M18 14.5v.01" />
                        </svg>
                    </span>
                    <p class="fh-pay__text"><strong>Pay when it arrives</strong><span>Cash on delivery on every order. No card needed.</span></p>
                </div>
                <?php $return_days = (int) (require __DIR__ . '/includes/legal_pages.php')['site']['return_days']; // the same promises the Book page makes ?>
                <ul class="fh-trust" aria-label="How buying works">
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 7h11v9H3z" /><path d="M14 10h4l3 3v3h-7z" /><circle cx="7" cy="18" r="1.6" /><circle cx="17" cy="18" r="1.6" />
                        </svg>
                        Free shipping over Rs. <?php echo number_format(K_FREE_SHIPPING_AT); ?>
                    </li>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 8h10a5 5 0 0 1 0 10H8" /><path d="M8 4 4 8l4 4" />
                        </svg>
                        Return within <?php echo $return_days; ?> days
                    </li>
                </ul>
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
    <section class="fl-sec fl-sec--band" aria-labelledby="fh-collections">
        <div class="fl-wrap">
            <div class="fl-head">
                <div>
                    <h2 class="fl-h2" id="fh-collections">Browse by collection</h2>
                    <p class="fl-sub">Start from what you love to read.</p>
                </div>
            </div>
            <ul class="fl-chips">
                <?php foreach ($genres as $genre): ?>
                    <li><a class="k-genre"
                            href="<?php echo BASE_URL; ?>frontend/shop.php?category=<?php echo urlencode($genre); ?>"><?php echo htmlspecialchars($genre); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
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
