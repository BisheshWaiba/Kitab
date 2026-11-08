<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch Orders
$sql_orders = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC";
$result_orders = $conn->query($sql_orders);

// Fetch User's Listed Books
// Check if column exists first to avoid error if script not run yet
$check_col = $conn->query("SHOW COLUMNS FROM books LIKE 'seller_id'");
$my_books = [];
if ($check_col->num_rows > 0) {
    $sql_my_books = "SELECT * FROM books WHERE seller_id = $user_id ORDER BY created_at DESC";
    $result_my_books = $conn->query($sql_my_books);
    if ($result_my_books) {
        while ($row = $result_my_books->fetch_assoc()) {
            $my_books[] = $row;
        }
    }
}

// Force reload user data to get latest profile pic
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
$avatar_path = trim((string) ($user['profile_picture'] ?? ''));
$avatar_url = $avatar_path === '' ? '' : (strpos($avatar_path, 'http') === 0 ? $avatar_path : BASE_URL . $avatar_path);
$initial = strtoupper(mb_substr($user_name, 0, 1));
?>

<section class="k-hello">
    <div class="k-hello__inner">
        <div class="k-hello__who">
            <?php if ($avatar_url !== ''): ?>
                <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="" class="k-hello__avatar">
            <?php else: ?>
                <div class="k-hello__avatar" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></div>
            <?php endif; ?>
            <div>
                <h1>Hello, <?php echo htmlspecialchars($user_name); ?></h1>
                <p>Welcome back to your dashboard</p>
            </div>
        </div>
        <div class="k-hello__actions">
            <a href="preferences.php" class="k-btn k-btn--ghost-light">Settings</a>
            <a href="sell_book.php" class="k-btn k-btn--light">Sell a Book</a>
            <a href="logout.php" class="k-btn k-btn--danger-light">Logout</a>
        </div>
    </div>
</section>

<div class="k-account">

    <!-- My Listings Section -->
    <section class="k-account__section">
        <div class="k-account__head">
            <h2 class="k-h2">My Listings</h2>
            <a href="sell_book.php" class="k-btn k-btn--primary k-btn--sm">Add New</a>
        </div>

        <?php if (!empty($my_books)): ?>
            <div class="k-grid">
                <?php foreach ($my_books as $book): ?>
                    <?php $book_href = 'product.php?id=' . (int) $book['id']; ?>
                    <article class="k-book">
                        <a class="k-book__cover" href="<?php echo $book_href; ?>" tabindex="-1" aria-hidden="true">
                            <?php k_cover($book); ?>
                        </a>
                        <div class="k-book__info">
                            <a class="k-book__title" href="<?php echo $book_href; ?>"><?php echo htmlspecialchars($book['title']); ?></a>
                            <span class="k-book__price">Rs. <?php echo number_format($book['price']); ?></span>
                        </div>
                        <a href="<?php echo $book_href; ?>" class="k-btn k-btn--outline k-btn--sm">View</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="k-empty">
                <p>You haven't listed any books for sale yet.</p>
                <a href="sell_book.php" class="k-btn k-btn--outline">List your first book</a>
            </div>
        <?php endif; ?>
    </section>

    <section class="k-account__section">
        <h2 class="k-h2">Order History</h2>

        <?php if ($result_orders->num_rows > 0): ?>
            <div class="k-orders">
                <?php while ($order = $result_orders->fetch_assoc()): ?>
                    <?php
                    $status = strtolower((string) $order['status']);
                    if (!in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
                        $status = 'pending';
                    }
                    ?>
                    <article class="k-order">
                        <div class="k-order__id">
                            <div class="k-order__num"><a href="order.php?id=<?php echo (int) $order['id']; ?>" class="k-order__link">Order #<?php echo (int) $order['id']; ?></a></div>
                            <div class="k-order__date"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="k-order__ship">
                            <div class="k-order__label">Shipping to</div>
                            <div class="k-order__value"><?php echo htmlspecialchars(k_order_address($order)); ?></div>
                        </div>
                        <div class="k-order__total">
                            <div class="k-order__label">Total</div>
                            <div class="k-order__amount">Rs. <?php echo number_format($order['total']); ?></div>
                        </div>
                        <div class="k-order__status">
                            <span class="k-pill k-pill--<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                            <a href="order.php?id=<?php echo (int) $order['id']; ?>" class="k-order__details">View details</a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="k-empty">
                <p>You haven't placed any orders yet.</p>
                <a href="shop.php" class="k-btn k-btn--primary">Start Shopping</a>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>
