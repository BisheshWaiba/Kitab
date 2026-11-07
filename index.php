<?php
require_once 'config/db.php';
require_once 'includes/helpers.php';
require_once 'includes/header.php';

// Hero shelf: the first seven books that have a cover
$hero_books = $conn->query("SELECT id, title, image FROM books WHERE image IS NOT NULL AND image <> '' ORDER BY id ASC LIMIT 7");

// Fetch Best Sellers (Random)
$sql_best = "SELECT * FROM books ORDER BY RAND() LIMIT 4";
$result_best = $conn->query($sql_best);

// Fetch New Arrivals (Latest)
$sql_new = "SELECT * FROM books ORDER BY created_at DESC LIMIT 4";
$result_new = $conn->query($sql_new);

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

$book_sections = [
    ['title' => 'Best Sellers', 'sub' => 'Books everyone is reading right now', 'books' => $result_best, 'band' => true],
    ['title' => 'New Arrivals', 'sub' => 'Fresh from the press', 'books' => $result_new, 'band' => false],
];
?>

<!-- Hero -->
<section class="k-hero">
    <div class="k-hero__inner">
        <h1>Discover Your Next <em>Favorite Book</em></h1>
        <div class="k-hero__side">
            <p>Explore thousands of titles from fiction to tech. Free shipping on orders over Rs. 2,000.</p>
            <div class="k-hero__cta">
                <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-btn k-btn--primary k-btn--lg">Shop Now</a>
                <a href="<?php echo BASE_URL; ?>frontend/register.php" class="k-btn k-btn--outline k-btn--lg">Join KITAB</a>
            </div>
        </div>
    </div>
    <div class="k-bookshelf">
        <div class="k-bookshelf__row">
            <?php while ($b = $hero_books->fetch_assoc()): ?>
                <a class="k-bookshelf__book" href="<?php echo BASE_URL; ?>frontend/product.php?id=<?php echo (int) $b['id']; ?>"
                    aria-label="<?php echo htmlspecialchars($b['title']); ?>">
                    <?php k_cover($b, 'k-bookshelf__img', false); ?>
                </a>
            <?php endwhile; ?>
        </div>
        <div class="k-bookshelf__plank"></div>
    </div>
</section>

<!-- Genres -->
<section class="k-genres">
    <h2 class="k-h2">Genres</h2>
    <ul class="k-genres__list">
        <?php foreach ($genres as $genre): ?>
            <li><a class="k-genre"
                    href="<?php echo BASE_URL; ?>frontend/shop.php?category=<?php echo urlencode($genre); ?>"><?php echo htmlspecialchars($genre); ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<!-- Best Sellers, New Arrivals -->
<?php foreach ($book_sections as $section): ?>
    <section class="<?php echo $section['band'] ? 'k-section--band' : ''; ?>">
        <div class="k-section__inner">
            <div class="k-section__head">
                <div>
                    <h2 class="k-h2"><?php echo $section['title']; ?></h2>
                    <p class="k-section__sub"><?php echo $section['sub']; ?></p>
                </div>
                <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-more">View All
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </a>
            </div>
            <div class="k-grid k-grid--shelf">
                <?php while ($row = $section['books']->fetch_assoc()): ?>
                    <?php k_book_card($row); ?>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
<?php endforeach; ?>

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
