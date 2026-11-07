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

$has_search = isset($_GET['search']);
$has_category = !empty($_GET['category']);
?>

<section class="k-pagehead">
    <div class="k-pagehead__inner">
        <h1 class="k-h1">All Books</h1>
        <p>Browse our complete catalog</p>
    </div>
</section>

<div class="k-shop">
    <main class="k-shop__main">
        <?php if ($has_search || $has_category): ?>
            <div class="k-filters">
                <?php if ($has_search): ?>
                    <p class="k-results">Search results for
                        "<strong><?php echo htmlspecialchars($_GET['search']); ?></strong>"</p>
                <?php endif; ?>
                <?php if ($has_category): ?>
                    <p class="k-results">Showing <strong><?php echo htmlspecialchars($_GET['category']); ?></strong>
                        <span aria-hidden="true">&middot;</span>
                        <a href="shop.php" class="k-results__clear">Show all books</a></p>
                <?php endif; ?>
            </div>
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
