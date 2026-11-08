<?php
// The page for any address that does not exist. PHP's built-in server reaches it through router.php,
// Apache through the rules in .htaccess.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
http_response_code(404);

$page_title = 'Page not found';
require_once __DIR__ . '/includes/header.php';

// Something to look at instead: the newest books
$result = $conn->query("SELECT * FROM books ORDER BY created_at DESC, id DESC LIMIT 4");
$books = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<section class="k-lost">
    <p class="k-lost__code" aria-hidden="true">4<span>0</span>4</p>
    <h1 class="k-h1">This page isn't on our shelves</h1>
    <p class="k-lost__text">
        The address may be mistyped, or the page may have moved. Try a search, or head back to the books.
    </p>

    <form action="<?php echo BASE_URL; ?>frontend/shop.php" method="GET" class="k-lost__search" role="search">
        <input type="text" name="search" class="k-input" placeholder="Title or author" aria-label="Search books">
        <button type="submit" class="k-btn k-btn--primary">Search</button>
    </form>

    <div class="k-lost__actions">
        <a href="<?php echo BASE_URL; ?>index.php" class="k-btn k-btn--outline">Go Home</a>
        <a href="<?php echo BASE_URL; ?>frontend/shop.php" class="k-btn k-btn--outline">Browse Books</a>
    </div>
</section>

<?php if ($books): ?>
    <section class="k-related">
        <h2 class="k-h2">New on the shelves</h2>
        <div class="k-grid">
            <?php foreach ($books as $row): ?>
                <?php k_book_card($row); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
