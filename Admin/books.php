<?php include 'includes/header.php';

// Fetch Books
$books = $conn->query("SELECT * FROM books ORDER BY id DESC");
?>

<main class="main-content">
    <header class="admin-header">
        <h1>Book Management</h1>
        <button class="btn-admin btn-admin-primary">
            <i class="fas fa-plus"></i> Add New Book
        </button>
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
                            <img src="<?php echo htmlspecialchars($book['image']); ?>"
                                style="width: 40px; height: 60px; object-fit: cover;">
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
                            <button class="btn-admin"
                                style="background: #f1f5f9; color: #475569; margin-right: 0.5rem;">Edit</button>
                            <button class="btn-admin" style="background: #fee2e2; color: #991b1b;">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

</body>

</html>