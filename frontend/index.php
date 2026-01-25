<?php
require_once '../config/db.php';
require_once '../includes/header.php';

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
            <h1>Discover Your Next <br><span style="color:var(--accent)">Favorite Book</span></h1>
            <p>Explore thousands of titles from fiction to tech. Free shipping on orders over Rs. 2,000.</p>
            <div style="display:flex; gap:1rem;">
                <a href="shop.php" class="btn btn-primary btn-lg">Shop Now</a>
                <a href="register.php" class="btn btn-outline btn-lg">Join KITAB</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="<?php echo BASE_URL; ?>assets/images/hero_bookstore.jpg" alt="Bookstore"
                style="box-shadow:var(--shadow-lg); border-radius:12px;">
        </div>
    </div>
</div>

<!-- Genre Carousel -->
<div class="container" style="margin-bottom: 4rem;">
    <h2 class="section-title-blue">Genres</h2>
    <div style="position: relative;">
        <button onclick="scrollGenres('left')" class="genre-scroll-btn genre-scroll-left">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="genre-scroll-container" id="genreScroll"
            style="display: flex; flex-direction: row; flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; gap: 1rem; scroll-behavior: smooth;">
            <a href="shop.php?category=Fiction" class="genre-item">
                <div class="genre-icon" style="background:#e0f2fe; color:#0284c7;">
                    <i class="fas fa-magic"></i>
                </div>
                <span>Fiction</span>
            </a>
            <a href="shop.php?category=Business" class="genre-item">
                <div class="genre-icon" style="background:#dcfce7; color:#16a34a;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span>Business</span>
            </a>
            <a href="shop.php?category=Tech" class="genre-item">
                <div class="genre-icon" style="background:#f3e8ff; color:#9333ea;">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <span>Tech</span>
            </a>
            <a href="shop.php?category=Science" class="genre-item">
                <div class="genre-icon" style="background:#fee2e2; color:#dc2626;">
                    <i class="fas fa-flask"></i>
                </div>
                <span>Science</span>
            </a>
            <a href="shop.php?category=History" class="genre-item">
                <div class="genre-icon" style="background:#ffedd5; color:#ea580c;">
                    <i class="fas fa-landmark"></i>
                </div>
                <span>History</span>
            </a>
            <a href="shop.php?category=Biography" class="genre-item">
                <div class="genre-icon" style="background:#fef3c7; color:#ca8a04;">
                    <i class="fas fa-user"></i>
                </div>
                <span>Biography</span>
            </a>
            <a href="shop.php?category=Self-Help" class="genre-item">
                <div class="genre-icon" style="background:#ddd6fe; color:#7c3aed;">
                    <i class="fas fa-heart"></i>
                </div>
                <span>Self-Help</span>
            </a>
            <a href="shop.php?category=Mystery" class="genre-item">
                <div class="genre-icon" style="background:#e0e7ff; color:#4f46e5;">
                    <i class="fas fa-search"></i>
                </div>
                <span>Mystery</span>
            </a>
            <a href="shop.php?category=Romance" class="genre-item">
                <div class="genre-icon" style="background:#fce7f3; color:#db2777;">
                    <i class="fas fa-heart-broken"></i>
                </div>
                <span>Romance</span>
            </a>
            <a href="shop.php?category=Fantasy" class="genre-item">
                <div class="genre-icon" style="background:#f0fdfa; color:#0d9488;">
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

<script>
    function scrollGenres(direction) {
        const container = document.getElementById('genreScroll');
        const scrollAmount = 300;
        if (direction === 'left') {
            container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        } else {
            container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    }
</script>

<!-- Best Sellers -->
<div class="book-section bg-light">
    <div class="container">
        <div class="section-header-flex">
            <div>
                <h2 class="section-title-blue">Best Sellers</h2>
                <p>Books everyone is reading right now</p>
            </div>
            <a href="shop.php" class="btn btn-outline">View All</a>
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
                        <a href="product.php?id=<?php echo $row['id']; ?>" class="btn btn-block btn-outline mt-2">ADD TO
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
            <a href="shop.php" class="btn btn-outline">View All</a>
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
                        <a href="product.php?id=<?php echo $row['id']; ?>" class="btn btn-block btn-outline mt-2">ADD TO
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
                                <a href="product.php?id=<?php echo $row['id']; ?>">
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
                        <div style="padding: 2rem; color: var(--text-light);">
                            No books found in this curated category yet.
                        </div>
                    <?php endif; ?>
                </div>
                <?php $first = false; endforeach; ?>
        </div>
    </div>
</div>

<script>
    function switchCategory(catId, btn) {
        // Update chips
        document.querySelectorAll('.filter-chip').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Update content
        document.querySelectorAll('.category-content').forEach(c => c.classList.remove('active'));
        document.getElementById(catId).classList.add('active');
    }
</script>

<?php require_once '../includes/footer.php'; ?>