<?php include 'includes/header.php';

// Fetch Books
$books = $conn->query("SELECT * FROM books ORDER BY id DESC");
?>

<main class="main-content">
    <header class="admin-header">
        <h1>Book Management</h1>
        <a href="<?php echo BASE_URL; ?>admin/add_book.php" class="btn-admin btn-admin-primary add-book-link">
            <i class="fas fa-plus"></i> Add New Book
        </a>
    </header>

    <div class="admin-card">
        <table class="admin-table">
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
                        <td>#
                            <?php echo $book['id']; ?>
                        </td>
                        <td>
                            <img src="<?php echo strpos($book['image'], 'http') === 0 ? htmlspecialchars($book['image']) : BASE_URL . htmlspecialchars($book['image']); ?>"
                                class="book-thumb">
                        </td>
                        <td>
                            <?php echo htmlspecialchars($book['title']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($book['author']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($book['category']); ?>
                        </td>
                        <td>Rs.
                            <?php echo number_format($book['price']); ?>
                        </td>
                        <td>
                            <button class="btn-admin btn-edit">Edit</button>
                            <button class="btn-admin btn-delete">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

</body>

</html>