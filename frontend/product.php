<?php
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM books WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<div class='container'>
    <p>Book not found.</p>
</div>";
    require_once '../includes/footer.php';
    exit();
}

$book = $result->fetch_assoc();

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $book_id = $book['id'];
    $quantity = intval($_POST['quantity']);

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$book_id])) {
        $_SESSION['cart'][$book_id] += $quantity;
    } else {
        $_SESSION['cart'][$book_id] = $quantity;
    }

    // Refresh to update header cart count
    echo "
<script>window.location.href = 'cart.php';</script>";
    exit();
}
?>

<div class="container product-container">
    <div class="product-grid">
        <div class="product-image">
            <img src="<?php echo strpos($book['image'], 'http') === 0 ? htmlspecialchars($book['image']) : BASE_URL . htmlspecialchars($book['image']); ?>"
                alt="<?php echo htmlspecialchars($book['title']); ?>">
        </div>
        <div class="product-details">
            <span class="category"><?php echo htmlspecialchars($book['category']); ?></span>
            <h1><?php echo htmlspecialchars($book['title']); ?></h1>
            <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
            <p class="price">Rs. <?php echo htmlspecialchars(number_format($book['price'])); ?></p>

            <div class="description">
                <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
            </div>

            <div class="product-specs">
                <h3>Product Details</h3>
                <ul class="specs-list">
                    <li><strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher'] ?? 'N/A'); ?></li>
                    <li><strong>Language:</strong> <?php echo htmlspecialchars($book['language'] ?? 'English'); ?></li>
                    <li><strong>Pages:</strong> <?php echo htmlspecialchars($book['pages'] ?? 'N/A'); ?></li>
                    <li><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></li>
                </ul>
            </div>

            <form method="POST" class="add-to-cart-form" style="display:flex; gap:1rem; align-items:flex-end;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="100" class="form-control"
                        style="width:100px;">
                </div>
                <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">Add to Cart</button>
            </form>
        </div>
    </div>

    <!-- Related Products -->
    <?php
    $current_id = $book['id'];
    $cat = $conn->real_escape_string($book['category']);
    $sql_related = "SELECT * FROM books WHERE category = '$cat' AND id != $current_id LIMIT 3";
    $result_related = $conn->query($sql_related);

    if ($result_related->num_rows > 0):
        ?>
        <div class="related-products">
            <h2 class="section-title-blue">You May Also Like</h2>
            <div class="grid books-grid">
                <?php while ($row = $result_related->fetch_assoc()): ?>
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
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>