<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Build Query
$where_clauses = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where_clauses[] = "(title LIKE '%$search%' OR author LIKE '%$search%')";
}

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = $conn->real_escape_string($_GET['category']);
    $where_clauses[] = "category = '$category'";
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

$sql = "SELECT * FROM books $where_sql ORDER BY created_at DESC";
$result = $conn->query($sql);

// Fetch Categories for Sidebar
$cat_sql = "SELECT DISTINCT category FROM books ORDER BY category ASC";
$cat_result = $conn->query($cat_sql);
?>

<div class="shop-header">
    <div class="container">
        <h1>All Books</h1>
        <p>Browse our complete catalog</p>
    </div>
</div>

<div class="container shop-container">
    <aside class="sidebar">
        <div class="filter-group">
            <h3>Categories</h3>
            <ul>
                <li><a href="shop.php" class="<?php echo !isset($_GET['category']) ? 'active' : ''; ?>">All
                        Categories</a></li>
                <?php while ($cat = $cat_result->fetch_assoc()): ?>
                    <li>
                        <a href="shop.php?category=<?php echo urlencode($cat['category']); ?>"
                            class="<?php echo (isset($_GET['category']) && $_GET['category'] == $cat['category']) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </aside>

    <main class="shop-content">
        <?php if (isset($_GET['search'])): ?>
            <p class="search-result-text">Search results for
                "<strong><?php echo htmlspecialchars($_GET['search']); ?></strong>"</p>
        <?php endif; ?>

        <div class="grid books-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
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
                            <a href="product.php?id=<?php echo $row['id']; ?>" class="btn btn-block btn-outline mt-2">View
                                Details</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>No books found matching your criteria.</p>
                    <a href="shop.php" class="btn btn-outline">Clear Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>