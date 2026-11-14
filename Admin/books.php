<?php include 'includes/header.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// What the admin asked for: words to look for (title, author or ISBN), and/or only the sold-out books or the ones running low
$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$stock_filter = in_array($_GET['stock'] ?? '', ['out', 'low'], true) ? $_GET['stock'] : '';

$where = [];
$types = '';
$args = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(title LIKE ? OR author LIKE ? OR isbn LIKE ?)';
    $types .= 'sss';
    array_push($args, $like, $like, $like);
}
if ($stock_filter === 'out') {
    $where[] = 'stock <= 0';
} elseif ($stock_filter === 'low') {
    $where[] = 'stock BETWEEN 1 AND 5';
}
$stmt = $conn->prepare('SELECT * FROM books' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC');
if ($args) {
    $stmt->bind_param($types, ...$args);
}
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// The numbers on the filter chips
[$count_all, $count_out, $count_low] = array_map('intval', $conn->query(
    "SELECT COUNT(*), COALESCE(SUM(stock <= 0), 0), COALESCE(SUM(stock BETWEEN 1 AND 5), 0) FROM books"
)->fetch_row());
// The address of this page with the stock filter changed (the search words travel along)
$link = function (string $stock) use ($q): string {
    $query = array_filter(['q' => $q, 'stock' => $stock], fn($v) => $v !== '');
    return 'books.php' . ($query ? '?' . htmlspecialchars(http_build_query($query), ENT_QUOTES) : '');
};

// One-time message left by delete_book.php
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<main class="k-admin__main">
    <div class="k-admin__bar">
        <h1 class="k-h1">Book Management</h1>
        <a href="<?php echo BASE_URL; ?>admin/add_book.php" class="k-btn k-btn--primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Add New Book
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="k-alert k-alert--<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>"
            role="<?php echo $flash['type'] === 'success' ? 'status' : 'alert'; ?>">
            <?php echo htmlspecialchars($flash['message']); ?></div>
    <?php endif; ?>

    <div class="k-toolbar">
        <form action="books.php" method="GET" class="k-search" role="search">
            <?php if ($stock_filter !== ''): ?><input type="hidden" name="stock" value="<?php echo $stock_filter; ?>"><?php endif; ?>
            <button type="submit" class="k-search__btn" aria-label="Search books">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
            </button>
            <input type="text" name="q" placeholder="Title, author or ISBN" aria-label="Search books" value="<?php echo htmlspecialchars($q); ?>">
        </form>
        <ul class="k-chips" aria-label="Filter by stock">
            <li><a href="<?php echo $link(''); ?>" class="k-genre<?php echo $stock_filter === '' ? ' is-active' : ''; ?>"<?php echo $stock_filter === '' ? ' aria-current="true"' : ''; ?>>All<span class="k-chips__n"><?php echo $count_all; ?></span></a></li>
            <li><a href="<?php echo $link('out'); ?>" class="k-genre<?php echo $stock_filter === 'out' ? ' is-active' : ''; ?>"<?php echo $stock_filter === 'out' ? ' aria-current="true"' : ''; ?>>Sold out<span class="k-chips__n"><?php echo $count_out; ?></span></a></li>
            <li><a href="<?php echo $link('low'); ?>" class="k-genre<?php echo $stock_filter === 'low' ? ' is-active' : ''; ?>"<?php echo $stock_filter === 'low' ? ' aria-current="true"' : ''; ?>>Running low (5 or fewer)<span class="k-chips__n"><?php echo $count_low; ?></span></a></li>
        </ul>
    </div>
    <?php if ($q !== '' || $stock_filter !== ''): ?>
        <p class="k-admin__count"><?php echo count($books) . (count($books) === 1 ? ' book' : ' books') . ' of ' . $count_all; ?>
            &middot; <a href="books.php" class="k-idlink">Clear</a></p>
    <?php endif; ?>

    <div class="k-tablecard">
        <table class="k-table k-table--wide k-table--stack k-table--books">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                    <?php $stock = (int) ($book['stock'] ?? 0); ?>
                    <tr>
                        <td class="k-num k-muted" data-label="ID">#<?php echo (int) $book['id']; ?></td>
                        <td><?php k_cover($book, 'k-thumb k-thumb--sm', false); ?></td>
                        <td class="k-admintitle"><?php echo htmlspecialchars($book['title']); ?></td>
                        <td class="k-muted" data-label="Author"><?php echo htmlspecialchars($book['author']); ?></td>
                        <td data-label="Category">
                            <?php if ((string) $book['category'] !== ''): ?>
                                <span class="k-tag"><?php echo htmlspecialchars($book['category']); ?></span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="k-num k-num--strong" data-label="Price">Rs. <?php echo number_format($book['price']); ?></td>
                        <td class="k-num" data-label="Stock"><?php echo $stock > 0 ? '<span class="k-stock">' . $stock . '</span>' : '<span class="k-pill k-pill--cancelled">Sold out</span>'; ?></td>
                        <td>
                            <div class="k-rowactions">
                                <a href="<?php echo BASE_URL; ?>admin/edit_book.php?id=<?php echo (int) $book['id']; ?>"
                                    class="k-btn k-btn--outline k-btn--xs"
                                    aria-label="Edit <?php echo htmlspecialchars($book['title']); ?>">Edit</a>
                                <form method="POST" action="<?php echo BASE_URL; ?>admin/delete_book.php"
                                    onsubmit="return confirm(this.dataset.confirm)"
                                    data-confirm="<?php echo htmlspecialchars('Delete "' . $book['title'] . '"? Its reviews are removed too, and this can\'t be undone.'); ?>">
                                    <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $book['id']; ?>">
                                    <button type="submit" class="k-btn k-btn--danger-light k-btn--xs"
                                        aria-label="Delete <?php echo htmlspecialchars($book['title']); ?>">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$books): ?>
                    <tr>
                        <?php if ($q !== '' || $stock_filter !== ''): ?>
                            <td colspan="8" class="k-table__empty">No books match. <a href="books.php">Show every book</a>.</td>
                        <?php else: ?>
                            <td colspan="8" class="k-table__empty">No books yet. <a href="<?php echo BASE_URL; ?>admin/add_book.php">Add the first one</a>.</td>
                        <?php endif; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>

</html>
