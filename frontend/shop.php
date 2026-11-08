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

// Books by one author (linked from the Authors page and the product page)
$author_filter = is_string($_GET['author'] ?? null) ? trim($_GET['author']) : '';
if ($author_filter !== '') {
    $author = $conn->real_escape_string($author_filter);
    $where_clauses[] = "TRIM(author) = '$author'";
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

// Category types shown above the books
$cat_result = $conn->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");
$active_category = $_GET['category'] ?? '';
?>

<section class="k-pagehead">
    <div class="k-pagehead__inner">
        <h1 class="k-h1">All Books</h1>
        <p>Browse our complete catalog</p>
    </div>
</section>

<div class="k-shop">
    <main class="k-shop__main">
        <nav aria-label="Book categories">
            <ul class="k-cats">
                <li><a href="shop.php" class="k-genre<?php echo $active_category === '' ? ' is-active' : ''; ?>"
                        <?php echo $active_category === '' ? 'aria-current="page"' : ''; ?>>All</a></li>
                <?php while ($cat = $cat_result->fetch_assoc()): ?>
                    <?php $is_active = ($active_category === $cat['category']); ?>
                    <li><a href="shop.php?category=<?php echo urlencode($cat['category']); ?>"
                            class="k-genre<?php echo $is_active ? ' is-active' : ''; ?>"
                            <?php echo $is_active ? 'aria-current="page"' : ''; ?>><?php echo htmlspecialchars($cat['category']); ?></a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </nav>

        <?php if ($author_filter !== ''): ?>
            <p class="k-results">Books by <strong><?php echo htmlspecialchars($author_filter); ?></strong>
                &middot; <a href="shop.php">Show all books</a></p>
        <?php endif; ?>

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
