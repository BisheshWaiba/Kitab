<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
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

<section class="k-pagehead">
    <div class="k-pagehead__inner">
        <h1 class="k-h1">All Books</h1>
        <p>Browse our complete catalog</p>
    </div>
</section>

<div class="k-shop">
    <aside class="k-side">
        <h2 class="k-side__title">Categories</h2>
        <ul class="k-side__list">
            <li><a href="shop.php" class="k-side__link<?php echo empty($_GET['category']) ? ' is-active' : ''; ?>"
                    <?php echo empty($_GET['category']) ? 'aria-current="page"' : ''; ?>>All Categories</a></li>
            <?php while ($cat = $cat_result->fetch_assoc()): ?>
                <?php $is_active = (isset($_GET['category']) && $_GET['category'] == $cat['category']); ?>
                <li>
                    <a href="shop.php?category=<?php echo urlencode($cat['category']); ?>"
                        class="k-side__link<?php echo $is_active ? ' is-active' : ''; ?>"
                        <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    </aside>

    <main class="k-shop__main">
        <?php if (isset($_GET['search'])): ?>
            <p class="k-results">Search results for
                "<strong><?php echo htmlspecialchars($_GET['search']); ?></strong>"</p>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
            <div class="k-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php k_book_card($row); ?>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="k-empty">
                <p>No books found matching your criteria.</p>
                <a href="shop.php" class="k-btn k-btn--outline">Clear Filters</a>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
