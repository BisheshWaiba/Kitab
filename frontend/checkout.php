<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Session first: the order is processed before the header is output, so its cart badge is up to date.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart_items = [];
$total_price = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
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
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

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

    // Build SQL query with proper NULL handling
    if ($user_id === null) {
        $sql_order = "INSERT INTO orders (user_id, name, email, address, total) VALUES (NULL, '$name', '$email', '$address', $total_price)";
    } else {
        $sql_order = "INSERT INTO orders (user_id, name, email, address, total) VALUES ($user_id, '$name', '$email', '$address', $total_price)";
    }

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

// The Checkout page has the glass look (from the design canvas): a body class and a stylesheet only the redesigned pages load
$body_class = 'is-glass is-checkout';
$extra_css = 'assets/css/glass.css';
$page_title = $order_placed ? 'Order placed' : 'Checkout';
require_once '../includes/header.php';

// Light from the books: the first two covers in the order glow softly behind the top of the page
$ambient = [];
foreach ($cart_items as $b) {
    $img = trim((string) $b['image']);
    if ($img !== '') {
        $ambient[] = strpos($img, 'http') === 0 ? $img : BASE_URL . $img;
        if (count($ambient) === 2) {
            break;
        }
    }
}
?>

<div class="hg-ambient" aria-hidden="true">
    <?php foreach ($ambient as $src): ?>
        <img src="<?php echo htmlspecialchars($src); ?>" alt="">
    <?php endforeach; ?>
</div>

<section class="gs-head hg-wrap">
    <h1 class="hg-h1 gs-title">Checkout</h1>
    <p class="hg-lede">You pay in cash when your order arrives.</p>
</section>

<div class="hg-wrap gs-main">
    <?php if ($order_placed): ?>
        <div class="gk-success hg-panel">
            <div class="gk-success__icon" aria-hidden="true">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="m5 12.5 4.5 4.5L19 7.5" />
                </svg>
            </div>
            <h2 class="hg-h2">Order Placed Successfully!</h2>
            <p>Thank you, <?php echo htmlspecialchars(trim((string) $_POST['name'])); ?>. Your order #<?php echo (int) $order_id; ?> has been received.</p>
            <div class="gk-success__actions">
                <?php if ($user_id !== null): ?>
                    <a href="order.php?id=<?php echo (int) $order_id; ?>" class="k-btn k-btn--outline">View Order</a>
                <?php endif; ?>
                <a href="shop.php" class="k-btn k-btn--primary">Continue Shopping</a>
            </div>
        </div>
    <?php else: ?>
        <div class="gk-grid">
            <form method="POST" class="gk-form hg-panel">
                <h2 class="hg-h2">Billing Details</h2>

                <div class="k-fields">
                    <div class="k-field">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="k-input" required placeholder="Ram Lama" autocomplete="name">
                    </div>
                    <div class="k-field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="k-input" required placeholder="ram@gmail.com" autocomplete="email">
                    </div>
                </div>
                <div class="k-field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="k-input" required placeholder="+977 98XXXXXXXX" autocomplete="tel">
                </div>
                <div class="k-field">
                    <label for="address">Shipping Address</label>
                    <textarea id="address" name="address" class="k-input k-textarea" rows="3" required
                        placeholder="Maitidevi, Kathmandu" autocomplete="street-address"></textarea>
                </div>

                <h2 class="hg-h2 gk-form__sub">Payment</h2>
                <label class="gk-pay">
                    <input type="radio" name="payment" value="cash_on_delivery" checked>
                    <span>
                        <strong>Cash on Delivery</strong>
                        <small>Pay in cash when your order arrives.</small>
                    </span>
                </label>

                <button type="submit" name="place_order" class="k-btn k-btn--primary k-btn--lg k-btn--block">Place Order (Rs.
                    <?php echo number_format($total_price); ?>)</button>
            </form>

            <aside class="gk-summary hg-panel" aria-label="Order summary">
                <h2 class="hg-h2">Order Summary</h2>
                <ul class="gk-items">
                    <?php foreach ($cart_items as $item): ?>
                        <li class="gk-item">
                            <?php k_cover($item, 'gk-item__img', false); ?>
                            <div class="gk-item__text">
                                <div class="gk-item__title"><?php echo htmlspecialchars($item['title']); ?></div>
                                <div class="gk-item__qty">Qty <?php echo (int) $item['qty']; ?></div>
                            </div>
                            <div class="gk-item__price">Rs. <?php echo number_format($item['price'] * $item['qty']); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="gk-total">
                    <span>Total</span>
                    <strong>Rs. <?php echo number_format($total_price); ?></strong>
                </div>
                <a href="cart.php" class="gk-edit">Edit cart</a>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
