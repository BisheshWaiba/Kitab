<?php
require_once 'config/db.php';
require_once 'includes/helpers.php';
require_once 'includes/reviews.php'; // k_review_name() for the review card in the hero
// The Home page has its own look (glass, from the design canvas): a body class and a stylesheet only this page loads
$body_class = 'is-glass is-home';
$extra_css = 'assets/css/glass.css';
require_once 'includes/header.php';

// Cover address, the same rule k_cover() uses
$cover_src = function (?string $img): string {
    $img = trim((string) $img);
    return $img === '' ? '' : (strpos($img, 'http') === 0 ? $img : BASE_URL . $img);
};

// Hero: four covers (books that have one) floating over the first screen
$hero_books = $conn->query("SELECT id, title, image FROM books WHERE image IS NOT NULL AND image <> '' ORDER BY id ASC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

// One real reader review for the hero card: the best-rated recent comment
$hero_review = $conn->query(
    "SELECT r.rating, r.comment, u.name, b.title
     FROM reviews r JOIN users u ON u.id = r.user_id JOIN books b ON b.id = r.book_id
     WHERE r.status = 'approved' AND TRIM(r.comment) <> ''
     ORDER BY r.rating DESC, r.created_at DESC, r.id DESC LIMIT 1"
)->fetch_assoc();

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

// Which hero cover goes in which spot (see .hg-c1 to .hg-c4 in home-glass.css)
$hero_order = [1 => 2, 2 => 0, 3 => 1, 4 => 3]; // slot => index in $hero_books
?>

<!-- Light from the books: blurred covers behind the first screen -->
<div class="hg-ambient" aria-hidden="true">
    <?php foreach (array_slice($hero_books, 0, 3) as $b): ?>
        <img src="<?php echo htmlspecialchars($cover_src($b['image'])); ?>" alt="">
    <?php endforeach; ?>
</div>

<!-- Hero -->
<section class="hg-hero hg-wrap">
    <div class="hg-hero__copy">
        <h1 class="hg-h1">Discover Your Next <em>Favorite Book</em></h1>
        <p class="hg-lede">Fiction, business, history, art and more, brought to your door. Pay when it arrives.</p>
        <div class="hg-hero__cta">
            <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-btn k-btn--primary k-btn--lg">Shop Now
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </a>
            <a href="<?php echo BASE_URL; ?>frontend/register.php" class="k-btn k-btn--outline k-btn--lg">Join KITAB</a>
        </div>
    </div>

    <div class="hg-hero__stage">
        <?php foreach ($hero_order as $slot => $i): ?>
            <?php if (isset($hero_books[$i])): $b = $hero_books[$i]; ?>
                <a class="hg-c hg-c<?php echo $slot; ?>" href="<?php echo BASE_URL; ?>frontend/product.php?id=<?php echo (int) $b['id']; ?>"
                    aria-label="<?php echo htmlspecialchars($b['title']); ?>">
                    <?php k_cover($b, 'hg-c__img', false); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($hero_review): ?>
            <div class="hg-review hg-panel">
                <span class="hg-stars" style="--p: <?php echo (int) $hero_review['rating'] * 20; ?>%" role="img"
                    aria-label="<?php echo (int) $hero_review['rating']; ?> out of 5 stars"></span>
                <p><?php echo htmlspecialchars($hero_review['comment']); ?></p>
                <small><?php echo htmlspecialchars(k_review_name((string) $hero_review['name'])); ?> on
                    <?php echo htmlspecialchars($hero_review['title']); ?></small>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Collections -->
<section class="hg-section hg-wrap" aria-labelledby="hg-collections">
    <div class="hg-collections hg-panel">
        <div>
            <h2 class="hg-h2" id="hg-collections">Browse by collection</h2>
            <p class="hg-sub">Start from what you love to read.</p>
        </div>
        <ul class="hg-chips">
            <?php foreach ($genres as $genre): ?>
                <li><a class="k-genre"
                        href="<?php echo BASE_URL; ?>frontend/shop.php?category=<?php echo urlencode($genre); ?>"><?php echo htmlspecialchars($genre); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- New Arrivals -->
<section class="hg-section hg-wrap" aria-labelledby="hg-new">
    <div class="hg-head">
        <div>
            <h2 class="hg-h2" id="hg-new">New Arrivals</h2>
            <p class="hg-sub">Fresh from the press</p>
        </div>
        <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-more">View all books
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14M13 6l6 6-6 6" />
            </svg>
        </a>
    </div>
    <div class="hg-grid">
        <?php while ($row = $result_new->fetch_assoc()): ?>
            <?php k_glass_tile($row); ?>
        <?php endwhile; ?>
    </div>
</section>

<!-- Best Sellers: books standing on a glass shelf -->
<section class="hg-section hg-wrap" aria-labelledby="hg-best">
    <div class="hg-shelf hg-panel">
        <div class="hg-shelf__copy">
            <h2 class="hg-h2" id="hg-best">Best Sellers</h2>
            <p class="hg-lede">Books everyone is reading right now.</p>
            <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-btn k-btn--primary k-btn--sm">See all</a>
        </div>
        <div class="hg-shelf__row">
            <?php while ($row = $result_best->fetch_assoc()): ?>
                <a class="hg-shelf__book" href="<?php echo BASE_URL; ?>frontend/product.php?id=<?php echo (int) $row['id']; ?>"
                    aria-label="<?php echo htmlspecialchars($row['title']); ?>, Rs. <?php echo number_format((float) $row['price']); ?>">
                    <?php k_cover($row, 'hg-shelf__img'); ?>
                    <span>Rs. <?php echo number_format((float) $row['price']); ?></span>
                </a>
            <?php endwhile; ?>
        </div>
        <div class="hg-shelf__plank" aria-hidden="true"></div>
    </div>
</section>

<!-- Our Picks -->
<section class="k-picks">
    <div class="k-picks__inner">
        <div class="k-picks__copy">
            <h2 class="k-h2 k-picks__title">Our picks for you</h2>
            <p class="k-picks__sub">We have curated special book collection for you</p>
            <div class="k-picks__chips">
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
        <div>
            <?php $first = true;
            foreach ($curated_data as $cat => $books): ?>
                <div id="picks-<?php echo trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($cat)), '-'); ?>"
                    class="k-picks__panel<?php echo $first ? ' is-active' : ''; ?>">
                    <?php if ($books->num_rows > 0): ?>
                        <?php while ($row = $books->fetch_assoc()): ?>
                            <a class="k-pick" href="<?php echo BASE_URL; ?>frontend/product.php?id=<?php echo (int) $row['id']; ?>">
                                <?php k_cover($row); ?>
                                <span class="k-pick__price">Rs. <?php echo number_format((float) $row['price']); ?></span>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="k-picks__empty">No books found in this curated category yet.</p>
                    <?php endif; ?>
                </div>
                <?php $first = false; endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
