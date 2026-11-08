<?php include 'includes/header.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Fetch Books
$books = $conn->query("SELECT * FROM books ORDER BY id DESC");

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

    <div class="k-tablecard">
        <table class="k-table k-table--wide">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($book = $books->fetch_assoc()): ?>
                    <tr>
                        <td class="k-num k-muted">#<?php echo (int) $book['id']; ?></td>
                        <td><?php k_cover($book, 'k-thumb k-thumb--sm', false); ?></td>
                        <td class="k-admintitle"><?php echo htmlspecialchars($book['title']); ?></td>
                        <td class="k-muted"><?php echo htmlspecialchars($book['author']); ?></td>
                        <td>
                            <?php if ((string) $book['category'] !== ''): ?>
                                <span class="k-tag"><?php echo htmlspecialchars($book['category']); ?></span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="k-num k-num--strong">Rs. <?php echo number_format($book['price']); ?></td>
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
                                    <button type="submit" class="k-btn k-btn--danger k-btn--xs"
                                        aria-label="Delete <?php echo htmlspecialchars($book['title']); ?>">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($books->num_rows == 0): ?>
                    <tr>
                        <td colspan="7" class="k-table__empty">No books yet. <a href="<?php echo BASE_URL; ?>admin/add_book.php">Add the first one</a>.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>

</html>
