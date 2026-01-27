<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$cart_items = [];
$total_price = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    if (!empty($ids)) {
        $sql = "SELECT * FROM books WHERE id IN ($ids)";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $row['qty'] = $_SESSION['cart'][$row['id']];
            $cart_items[] = $row;
            $total_price += $row['price'] * $row['qty'];
        }
    }
}

if (empty($cart_items)) {
    header("Location: shop.php");
    exit();
}

$order_placed = false;

// Handle Order Submission
if (isset($_POST['place_order'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $address = $conn->real_escape_string($_POST['address']);
    $email = $conn->real_escape_string($_POST['email']);

    // Insert Order
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NULL';

    // Self-healing: Ensure required columns exist in orders table
    $check_name = $conn->query("SHOW COLUMNS FROM orders LIKE 'name'");
    if ($check_name && $check_name->num_rows == 0) {
        $conn->query("ALTER TABLE orders ADD name VARCHAR(255) AFTER user_id");
    }

    $check_email = $conn->query("SHOW COLUMNS FROM orders LIKE 'email'");
    if ($check_email && $check_email->num_rows == 0) {
        $conn->query("ALTER TABLE orders ADD email VARCHAR(255) AFTER name");
    }

    $check_address = $conn->query("SHOW COLUMNS FROM orders LIKE 'address'");
    if ($check_address && $check_address->num_rows == 0) {
        $conn->query("ALTER TABLE orders ADD address TEXT AFTER email");
    }

    $sql_order = "INSERT INTO orders (user_id, name, email, address, total) VALUES ($user_id, '$name', '$email', '$address',
$total_price)";
    if ($conn->query($sql_order)) {
        $order_id = $conn->insert_id;

        // Insert Order Items
        foreach ($cart_items as $item) {
            $book_id = $item['id'];
            $quantity = $item['qty'];
            $price = $item['price'];
            $sql_item = "INSERT INTO order_items (order_id, book_id, quantity, price) VALUES ($order_id, $book_id, $quantity,
$price)";
            $conn->query($sql_item);
        }

        // Clear Cart
        unset($_SESSION['cart']);
        $order_placed = true;
    }
}
?>

<div class="container" style="padding: 4rem 2rem;">
    <h1>Checkout</h1>

    <?php if ($order_placed): ?>
        <div class="success-message" style="text-align:center; padding:3rem; background:#f0fdf4; border-radius:12px;">
            <div style="font-size:3rem; color:#16a34a; margin-bottom:1rem;"><i class="fas fa-check-circle"></i></div>
            <h2>Order Placed Successfully!</h2>
            <p>Thank you, <?php echo htmlspecialchars($name); ?>. Your order #<?php echo $order_id; ?> has been received.
            </p>
            <a href="shop.php" class="btn btn-primary" style="margin-top:1rem;">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="checkout-grid">
            <div class="card">
                <h3>Billing Details</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="Ram Lama">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="ram@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" required placeholder="+977 98XXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Shipping Address</label>
                        <textarea name="address" class="form-control" rows="4" required
                            placeholder="Maitidevi, Kathmandu"></textarea>
                    </div>

                    <h3>Payment</h3>
                    <div class="payment-method">
                        <div class="form-group">
                            <label><input type="radio" name="payment" checked> Cash on Delivery</label>
                        </div>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-primary btn-block">Place Order (Rs.
                        <?php echo number_format($total_price); ?>)</button>
                </form>
            </div>

            <div class="card">
                <h3>Order Summary</h3>
                <div class="order-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div
                            style="display:flex; justify-content:space-between; margin-bottom:1rem; border-bottom:1px dashed #e2e8f0; padding-bottom:0.5rem;">
                            <div>
                                <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                <br>
                                <small>x<?php echo $item['qty']; ?></small>
                            </div>
                            <span>Rs. <?php echo number_format($item['price'] * $item['qty']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div
                    style="display:flex; justify-content:space-between; font-size:1.2rem; font-weight:700; color:var(--primary); margin-top:1rem;">
                    <span>Total</span>
                    <span>Rs. <?php echo number_format($total_price); ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>