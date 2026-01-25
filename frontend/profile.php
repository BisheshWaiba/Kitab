<?php
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
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
?>


<div class="profile-header">
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:1.5rem;">
                <!-- Dynamic Avatar -->
                <?php
                // Force reload user data to get latest profile pic
                $user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
                $avatar_url = !empty($user['profile_picture']) ? $user['profile_picture'] : "https://ui-avatars.com/api/?name=" . urlencode($user_name) . "&background=random&color=fff&size=128";
                ?>
                <img src="<?php echo strpos($avatar_url, 'http') === 0 ? htmlspecialchars($avatar_url) : BASE_URL . htmlspecialchars($avatar_url); ?>"
                    alt="Profile" class="profile-avatar" style="object-fit:cover; background: white;">
                <div>
                    <h1 style="color:white; margin:0; font-size:2rem;">Hello,
                        <?php echo htmlspecialchars($user_name); ?>
                    </h1>
                    <p style="opacity:0.8; color:white; margin:0;">Welcome back to your dashboard</p>
                </div>
            </div>
            <div style="display:flex; gap:1rem; align-items:center;">
                <a href="preferences.php" class="btn"
                    style="background:rgba(255,255,255,0.15); color:white; border:1px solid rgba(255,255,255,0.3); backdrop-filter:blur(10px);">
                    <i class="fas fa-cog" style="margin-right:0.5rem;"></i> Settings
                </a>
                <a href="sell_book.php" class="btn btn-light"
                    style="font-weight:600; border:2px solid rgba(255,255,255,0.5);">
                    <i class="fas fa-plus-circle" style="margin-right:0.5rem;"></i> Sell a Book
                </a>
                <a href="logout.php" class="btn"
                    style="background:rgba(239,68,68,0.9); color:white; border:none; font-weight:600;">
                    <i class="fas fa-sign-out-alt" style="margin-right:0.5rem;"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding-bottom: 4rem;">

    <!-- My Listings Section -->
    <div style="margin-bottom: 4rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2>My Listings</h2>
            <a href="sell_book.php" class="btn btn-primary btn-sm">Add New</a>
        </div>

        <?php if (!empty($my_books)): ?>
            <div class="grid books-grid">
                <?php foreach ($my_books as $book): ?>
                    <div class="card book-card">
                        <div class="book-image" style="height: 200px;">
                            <img src="<?php echo strpos($book['image'], 'http') === 0 ? htmlspecialchars($book['image']) : BASE_URL . htmlspecialchars($book['image']); ?>"
                                alt="<?php echo htmlspecialchars($book['title']); ?>">
                        </div>
                        <div class="book-info">
                            <h3 style="font-size:1rem;"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="price" style="font-size:1rem;">Rs. <?php echo number_format($book['price']); ?></p>
                            <div style="display:flex; gap:0.5rem; margin-top:auto;">
                                <a href="product.php?id=<?php echo $book['id']; ?>" class="btn btn-outline"
                                    style="flex:1; font-size:0.8rem; padding:0.4rem;">View</a>
                                <!-- In a real app, delete would go here -->
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div
                style="text-align:center; padding:2rem; background:#f8fafc; border-radius:12px; border:1px dashed #cbd5e1;">
                <p style="color:var(--text-light);">You haven't listed any books for sale yet.</p>
                <a href="sell_book.php" style="color:var(--accent); font-weight:600;">List your first book</a>
            </div>
        <?php endif; ?>
    </div>

    <h2>Order History</h2>

    <?php if ($result_orders->num_rows > 0): ?>
        <div class="grid" style="grid-template-columns: 1fr;">
            <?php while ($order = $result_orders->fetch_assoc()): ?>
                <div class="card order-card">
                    <div
                        style="display:flex; justify-content:space-between; margin-bottom:1rem; border-bottom:1px solid #f1f5f9; padding-bottom:0.5rem;">
                        <div>
                            <span style="font-weight:700; color:var(--primary);">Order #<?php echo $order['id']; ?></span>
                            <span
                                style="color:var(--text-light); margin-left:0.5rem; font-size:0.9rem;"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                        </div>
                        <span class="order-status"><?php echo ucfirst($order['status']); ?></span>
                    </div>
                    <div>
                        <p><strong>Total:</strong> Rs. <?php echo number_format($order['total']); ?></p>
                        <p><strong>Shipping to:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding:3rem; background:#f8fafc; border-radius:12px;">
            <p>You haven't placed any orders yet.</p>
            <a href="shop.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>