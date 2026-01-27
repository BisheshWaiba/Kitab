<?php
require_once 'config/db.php';
require_once 'includes/header.php';

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
?>

<!-- Hero Section -->
<div class="hero">
    <div class="container hero-container">
        <div class="hero-content">
            <h1>Discover Your Next <br><span class="text-accent">Favorite Book</span></h1>
            <p>Explore thousands of titles from fiction to tech. Free shipping on orders over Rs. 2,000.</p>
            <div style="display:flex; gap:1rem;">
                <a href="frontend/shop.php" class="btn btn-primary btn-lg">Shop Now</a>
                <a href="frontend/register.php" class="btn btn-outline btn-lg">Join KITAB</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="<?php echo BASE_URL; ?>assets/images/hero_bookstore.jpg" alt="Bookstore" class="hero-img-styled">
        </div>
    </div>
</div>

<!-- Genre Carousel -->
<div class="container section-spacing">
    <h2 class="section-title-blue">Genres</h2>
    <div class="relative">
        <button onclick="scrollGenres('left')" class="genre-scroll-btn genre-scroll-left">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="genre-scroll-container genre-scroll-wrapper" id="genreScroll">
            <a href="frontend/shop.php?category=Fiction" class="genre-item">
                <div class="genre-icon genre-fiction">
                    <i class="fas fa-magic"></i>
                </div>
                <span>Fiction</span>
            </a>
            <a href="frontend/shop.php?category=Business" class="genre-item">
                <div class="genre-icon genre-business">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span>Business</span>
            </a>
            <a href="frontend/shop.php?category=Tech" class="genre-item">
                <div class="genre-icon genre-tech">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <span>Tech</span>
            </a>
            <a href="frontend/shop.php?category=Science" class="genre-item">
                <div class="genre-icon genre-science">
                    <i class="fas fa-flask"></i>
                </div>
                <span>Science</span>
            </a>
            <a href="frontend/shop.php?category=History" class="genre-item">
                <div class="genre-icon genre-history">
                    <i class="fas fa-landmark"></i>
                </div>
                <span>History</span>
            </a>
            <a href="frontend/shop.php?category=Biography" class="genre-item">
                <div class="genre-icon genre-biography">
                    <i class="fas fa-user"></i>
                </div>
                <span>Biography</span>
            </a>
            <a href="frontend/shop.php?category=Self-Help" class="genre-item">
                <div class="genre-icon genre-self-help">
                    <i class="fas fa-heart"></i>
                </div>
                <span>Self-Help</span>
            </a>
            <a href="frontend/shop.php?category=Mystery" class="genre-item">
                <div class="genre-icon genre-mystery">
                    <i class="fas fa-search"></i>
                </div>
                <span>Mystery</span>
            </a>
            <a href="frontend/shop.php?category=Romance" class="genre-item">
                <div class="genre-icon genre-romance">
                    <i class="fas fa-heart-broken"></i>
                </div>
                <span>Romance</span>
            </a>
            <a href="frontend/shop.php?category=Fantasy" class="genre-item">
                <div class="genre-icon genre-fantasy">
                    <i class="fas fa-dragon"></i>
                </div>
                <span>Fantasy</span>
            </a>
        </div>
        <button onclick="scrollGenres('right')" class="genre-scroll-btn genre-scroll-right">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>




<!-- Best Sellers -->
<div class="book-section bg-light">
    <div class="container">
        <div class="section-header-flex">
            <div>
                <h2 class="section-title-blue">Best Sellers</h2>
                <p>Books everyone is reading right now</p>
            </div>
            <a href="frontend/shop.php" class="btn btn-outline">View All</a>
        </div>

        <div class="grid books-grid">
            <?php while ($row = $result_best->fetch_assoc()): ?>
                <div class="card book-card">
                    <div class="book-image">
                        <img src="<?php echo strpos($row['image'], 'http') === 0 ? htmlspecialchars($row['image']) : BASE_URL . htmlspecialchars($row['image']); ?>"
                            alt="<?php echo htmlspecialchars($row['title']); ?>">
                    </div>
                    <div class="book-info">
                        <span class="category"><?php echo htmlspecialchars($row['category']); ?></span>
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p class="author">by <?php echo htmlspecialchars($row['author']); ?></p>
                        <div class="book-footer">
                            <span class="price">Rs.
                                <?php echo htmlspecialchars(number_format($row['price'])); ?></span>
                        </div>
                        <a href="frontend/product.php?id=<?php echo $row['id']; ?>"
                            class="btn btn-block btn-outline mt-2">ADD TO
                            CART</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- New Arrivals -->
<div class="book-section">
    <div class="container">
        <div class="section-header-flex">
            <div>
                <h2 class="section-title-blue">New Arrivals</h2>
                <p>Fresh from the press</p>
            </div>
            <a href="frontend/shop.php" class="btn btn-outline">View All</a>
        </div>

        <div class="grid books-grid">
            <?php while ($row = $result_new->fetch_assoc()): ?>
                <div class="card book-card">
                    <div class="book-image">
                        <img src="<?php echo strpos($row['image'], 'http') === 0 ? htmlspecialchars($row['image']) : BASE_URL . htmlspecialchars($row['image']); ?>"
                            alt="<?php echo htmlspecialchars($row['title']); ?>">
                    </div>
                    <div class="book-info">
                        <span class="category"><?php echo htmlspecialchars($row['category']); ?></span>
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p class="author">by <?php echo htmlspecialchars($row['author']); ?></p>
                        <div class="book-footer">
                            <span class="price">Rs.
                                <?php echo htmlspecialchars(number_format($row['price'])); ?></span>
                        </div>
                        <a href="frontend/product.php?id=<?php echo $row['id']; ?>"
                            class="btn btn-block btn-outline mt-2">ADD TO
                            CART</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Our Picks Section -->
<div class="curated-section">
    <div class="container curated-layout">
        <div class="curated-content">
            <h2>Our picks for you</h2>
            <p>We have curated special book collection for you</p>
            <div class="curated-filters">
                <?php $first = true;
                foreach ($curated_cats as $cat): ?>
                    <button class="filter-chip <?php echo $first ? 'active' : ''; ?>"
                        onclick="switchCategory('<?php echo str_replace(' ', '-', strtolower($cat)); ?>', this)">
                        <?php echo htmlspecialchars($cat); ?>
                    </button>
                    <?php $first = false; endforeach; ?>
            </div>
        </div>
        <div class="curated-display">
            <?php $first = true;
            foreach ($curated_data as $cat => $books): ?>
                <div id="<?php echo str_replace(' ', '-', strtolower($cat)); ?>"
                    class="curated-grid category-content <?php echo $first ? 'active' : ''; ?>">
                    <?php if ($books->num_rows > 0): ?>
                        <?php while ($row = $books->fetch_assoc()): ?>
                            <div class="curated-book-card">
                                <a href="frontend/product.php?id=<?php echo $row['id']; ?>">
                                    <div class="curated-book-image">
                                        <img src="<?php echo strpos($row['image'], 'http') === 0 ? htmlspecialchars($row['image']) : BASE_URL . htmlspecialchars($row['image']); ?>"
                                            alt="<?php echo htmlspecialchars($row['title']); ?>">
                                    </div>
                                    <div class="curated-book-price">
                                        Rs. <?php echo htmlspecialchars(number_format($row['price'])); ?>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="curated-empty">
                            No books found in this curated category yet.
                        </div>
                    <?php endif; ?>
                </div>
                <?php $first = false; endforeach; ?>
        </div>
    </div>
</div>




<?php require_once 'includes/footer.php'; ?>