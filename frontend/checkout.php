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

// What was typed, kept when a field needs another look, and the problems found (field => message, shown under the field)
$old = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
$errors = [];
if (isset($_POST['place_order'])) {
    foreach ($old as $key => $unused) {
        $old[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    }
    if ($old['name'] === '') {
        $errors['name'] = "Enter your full name.";
    } elseif (mb_strlen($old['name']) > 100) {
        $errors['name'] = "Use 100 characters or fewer.";
    }
    if ($old['email'] === '') {
        $errors['email'] = "Enter your email address.";
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 255) {
        $errors['email'] = "Enter a valid email address, like name@example.com.";
    }
    $phone_digits = strlen(preg_replace('/\D/', '', $old['phone']));
    if ($old['phone'] === '') {
        $errors['phone'] = "Enter a phone number we can reach you on.";
    } elseif (!preg_match('/^[+0-9 ()\-]+$/', $old['phone']) || $phone_digits < 7 || $phone_digits > 15) {
        $errors['phone'] = "Enter a phone number with 7 to 15 digits, like +977 98XXXXXXXX.";
    }
    if ($old['address'] === '') {
        $errors['address'] = "Enter the address we should deliver to.";
    } elseif (mb_strlen($old['address']) > 500) {
        $errors['address'] = "Use 500 characters or fewer.";
    }
}

// Saved details: a logged-in customer's form starts filled in from their account, and the delivery address from their
// last order when the account has none. Every field can still be changed. (Not on a form that came back with messages:
// that one keeps exactly what was typed.)
$prefilled = false;
if (!isset($_POST['place_order']) && isset($_SESSION['user_id'])) {
    try {
        $uid = (int) $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT name, email, phone, address FROM users WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $saved = $stmt->get_result()->fetch_assoc() ?: [];
        foreach ($old as $key => $unused) {
            $old[$key] = trim((string) ($saved[$key] ?? ''));
        }
        if ($old['address'] === '') {
            $stmt = $conn->prepare("SELECT address, shipping_address FROM orders
                WHERE user_id = ? AND (COALESCE(address, '') <> '' OR COALESCE(shipping_address, '') <> '')
                ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $old['address'] = k_order_address($stmt->get_result()->fetch_assoc() ?: []);
        }
        $prefilled = implode('', $old) !== '';
    } catch (Throwable $e) {
        error_log('KITAB checkout: could not load the saved details: ' . $e->getMessage()); // the form just starts empty
    }
}

// Handle Order Submission (only when every field is fine; otherwise the form comes back with the messages)
if (isset($_POST['place_order']) && !$errors) {
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

// The Checkout page is flat (white and light-grey surfaces, from the design canvas); only its buttons are glass.
// is-gbtn switches the glass buttons on, and these stylesheets are loaded by the redesigned pages only
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/checkout-flat.css'];
$page_title = $order_placed ? 'Order placed' : 'Checkout';
require_once '../includes/header.php';
?>

<main class="fl">

<section class="gk-head fl-wrap">
    <h1 class="fl-h1">Checkout</h1>
    <p class="fl-sub">You pay in cash when your order arrives.</p>
    <?php k_checkout_steps($order_placed ? 'done' : 'details'); ?>
</section>

<div class="fl-wrap gk-main">
    <?php if ($order_placed): ?>
        <div class="gk-success fl-panel">
            <div class="gk-success__icon" aria-hidden="true">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="m5 12.5 4.5 4.5L19 7.5" />
                </svg>
            </div>
            <h2 class="fl-h2">Order Placed Successfully!</h2>
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
            <form method="POST" class="gk-form fl-panel" data-validate>
                <h2 class="fl-h2">Billing Details</h2>
                <?php if ($prefilled): ?>
                    <p class="gk-form__note">We filled this in from your account and your last order. Change anything that is different.</p>
                <?php endif; ?>

                <div class="k-fields">
                    <div class="k-field">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="k-input" required placeholder="Ram Lama" autocomplete="name"
                            maxlength="100" data-msg-missing="Enter your full name."
                            value="<?php echo htmlspecialchars($old['name']); ?>" <?php k_field_invalid($errors, 'name'); ?>>
                        <?php k_field_error($errors, 'name'); ?>
                    </div>
                    <div class="k-field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="k-input" required placeholder="ram@gmail.com" autocomplete="email"
                            maxlength="255" data-msg-missing="Enter your email address."
                            value="<?php echo htmlspecialchars($old['email']); ?>" <?php k_field_invalid($errors, 'email'); ?>>
                        <?php k_field_error($errors, 'email'); ?>
                    </div>
                </div>
                <div class="k-field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="k-input" required placeholder="+977 98XXXXXXXX" autocomplete="tel"
                        pattern="[+0-9 \(\)\-]{7,25}" maxlength="25" data-msg-missing="Enter a phone number we can reach you on."
                        data-msg-invalid="Enter a phone number with 7 to 15 digits, like +977 98XXXXXXXX."
                        value="<?php echo htmlspecialchars($old['phone']); ?>" <?php k_field_invalid($errors, 'phone'); ?>>
                    <?php k_field_error($errors, 'phone'); ?>
                </div>
                <div class="k-field">
                    <label for="address">Shipping Address</label>
                    <textarea id="address" name="address" class="k-input k-textarea" rows="3" required maxlength="500"
                        placeholder="Maitidevi, Kathmandu" autocomplete="street-address"
                        data-msg-missing="Enter the address we should deliver to."
                        <?php k_field_invalid($errors, 'address'); ?>><?php echo htmlspecialchars($old['address']); ?></textarea>
                    <?php k_field_error($errors, 'address'); ?>
                </div>

                <h2 class="fl-h2 gk-form__sub">Payment</h2>
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

            <aside class="gk-summary fl-panel" aria-label="Order summary">
                <h2 class="fl-h2">Order Summary</h2>
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

</main>

<?php require_once '../includes/footer.php'; ?>
