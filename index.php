<?php
require_once 'config/db.php';
require_once 'includes/helpers.php';
// The Home page is flat (white and light-grey surfaces, from the design canvas); only its buttons are glass.
// is-gbtn switches the glass buttons on, and both stylesheets are loaded by this page alone.
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/home-flat.css'];
require_once 'includes/header.php';

// Hero: three covers (books that have one) standing on a shelf
$hero_books = $conn->query("SELECT id, title, image FROM books WHERE image IS NOT NULL AND image <> '' ORDER BY id ASC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

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

// Which hero cover stands in which spot on the shelf (left, middle and right): index in $hero_books
$shelf_order = [2, 0, 1];
?>

<main class="fh">

    <!-- Hero -->
    <section class="fh-wrap fh-hero">
        <div class="fh-hero__copy">
            <h1 class="fh-h1">Discover Your Next <em>Favorite Book</em></h1>
            <p class="fh-lede">Fiction, business, history, art and more, brought to your door. Pay when it arrives.</p>
            <div class="fh-hero__cta">
                <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-btn k-btn--primary k-btn--lg">Shop Now
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </a>
                <a href="<?php echo BASE_URL; ?>frontend/register.php" class="k-btn k-btn--outline k-btn--lg">Join KITAB</a>
            </div>
        </div>

        <div class="fh-shelf">
            <?php foreach ($shelf_order as $i): ?>
                <?php if (isset($hero_books[$i])): $b = $hero_books[$i]; ?>
                    <a href="<?php echo BASE_URL; ?>frontend/product.php?id=<?php echo (int) $b['id']; ?>"
                        aria-label="<?php echo htmlspecialchars($b['title']); ?>">
                        <?php k_cover($b, 'fh-cover', false); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Collections -->
    <section class="fh-sec fh-sec--band" aria-labelledby="fh-collections">
        <div class="fh-wrap">
            <div class="fh-head">
                <div>
                    <h2 class="fh-h2" id="fh-collections">Browse by collection</h2>
                    <p class="fh-sub">Start from what you love to read.</p>
                </div>
            </div>
            <ul class="fh-chips">
                <?php foreach ($genres as $genre): ?>
                    <li><a class="k-genre"
                            href="<?php echo BASE_URL; ?>frontend/shop.php?category=<?php echo urlencode($genre); ?>"><?php echo htmlspecialchars($genre); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- New Arrivals -->
    <section class="fh-sec" aria-labelledby="fh-new">
        <div class="fh-wrap">
            <div class="fh-head">
                <div>
                    <h2 class="fh-h2" id="fh-new">New Arrivals</h2>
                    <p class="fh-sub">Fresh from the press</p>
                </div>
                <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-more">View all books
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </a>
            </div>
            <div class="fh-grid">
                <?php while ($row = $result_new->fetch_assoc()): ?>
                    <?php k_book_card($row, 'primary'); ?>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Best Sellers -->
    <section class="fh-sec fh-sec--band" aria-labelledby="fh-best">
        <div class="fh-wrap fh-best">
            <div class="fh-best__copy">
                <h2 class="fh-h2" id="fh-best">Best Sellers</h2>
                <p class="fh-lede">Books everyone is reading right now.</p>
                <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-btn k-btn--primary">See all</a>
            </div>
            <div class="fh-best__row">
                <?php while ($row = $result_best->fetch_assoc()): ?>
                    <a class="fh-best__book" href="<?php echo BASE_URL; ?>frontend/product.php?id=<?php echo (int) $row['id']; ?>"
                        aria-label="<?php echo htmlspecialchars($row['title']); ?>, Rs. <?php echo number_format((float) $row['price']); ?>">
                        <?php k_cover($row, 'fh-cover'); ?>
                        <span class="fh-best__price">Rs. <?php echo number_format((float) $row['price']); ?></span>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Our Picks -->
    <section class="fh-sec" aria-labelledby="fh-picks">
        <div class="fh-wrap">
            <div class="fh-head">
                <div>
                    <h2 class="fh-h2" id="fh-picks">Our picks for you</h2>
                    <p class="fh-sub">We have curated special book collection for you</p>
                </div>
                <div class="fh-chips">
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
