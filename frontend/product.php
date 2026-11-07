<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
require_once '../includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM books WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<div class='k-page'>
    <div class='k-empty'>
        <p>Book not found.</p>
        <a href='shop.php' class='k-btn k-btn--outline'>Back to Shop</a>
    </div>
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

$description = trim((string) ($book['description'] ?? ''));
?>

<section class="k-product">
    <div class="k-product__media">
        <?php k_cover($book, 'k-product__cover', false); ?>
    </div>

    <div class="k-product__info">
        <span class="k-product__cat"><?php echo htmlspecialchars($book['category']); ?></span>
        <h1 class="k-product__title"><?php echo htmlspecialchars($book['title']); ?></h1>
        <p class="k-product__author">by <?php echo htmlspecialchars($book['author']); ?></p>
        <p class="k-product__price">Rs. <?php echo htmlspecialchars(number_format($book['price'])); ?></p>

        <?php if ($description !== ''): ?>
            <p class="k-product__desc"><?php echo nl2br(htmlspecialchars($description)); ?></p>
        <?php endif; ?>

        <div class="k-specs">
            <h2 class="k-specs__title">Product Details</h2>
            <dl>
                <div>
                    <dt>Publisher</dt>
                    <dd><?php echo htmlspecialchars($book['publisher'] ?: 'N/A'); ?></dd>
                </div>
                <div>
                    <dt>Language</dt>
                    <dd><?php echo htmlspecialchars($book['language'] ?? 'English'); ?></dd>
                </div>
                <div>
                    <dt>Pages</dt>
                    <dd><?php echo htmlspecialchars($book['pages'] ?: 'N/A'); ?></dd>
                </div>
                <div>
                    <dt>ISBN</dt>
                    <dd><?php echo htmlspecialchars($book['isbn'] ?: 'N/A'); ?></dd>
                </div>
            </dl>
        </div>

        <form method="POST" class="k-buy">
            <div class="k-qty">
                <label for="quantity">Quantity</label>
                <div class="k-qty__box" data-qty>
                    <button type="button" class="k-qty__btn" data-step="-1" aria-label="Decrease quantity">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M5 12h14" />
                        </svg>
                    </button>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="100">
                    <button type="button" class="k-qty__btn" data-step="1" aria-label="Increase quantity">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit" name="add_to_cart" class="k-btn k-btn--primary">Add to Cart</button>
        </form>
    </div>
</section>

<!-- Related Products -->
<?php
$current_id = $book['id'];
$cat = $conn->real_escape_string($book['category']);
$sql_related = "SELECT * FROM books WHERE category = '$cat' AND id != $current_id LIMIT 3";
$result_related = $conn->query($sql_related);

if ($result_related->num_rows > 0):
    ?>
    <section class="k-related">
        <h2 class="k-h2">You May Also Like</h2>
        <div class="k-grid">
            <?php while ($row = $result_related->fetch_assoc()): ?>
                <?php k_book_card($row); ?>
            <?php endwhile; ?>
        </div>
    </section>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
