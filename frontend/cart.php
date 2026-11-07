<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
require_once '../includes/header.php';

// Handle Remove
if (isset($_GET['remove'])) {
    $id = intval($_GET['remove']);
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
    header("Location: cart.php");
    exit();
}

// Handle Update Quantity
if (isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $id => $qty) {
        $id = intval($id);
        $qty = intval($qty);
        if ($qty > 0) {
            $_SESSION['cart'][$id] = $qty;
        } else {
            unset($_SESSION['cart'][$id]);
        }
    }
    header("Location: cart.php");
    exit();
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
?>

<div class="k-page k-page--stack">
    <h1 class="k-h1">Shopping Cart</h1>

    <?php if (empty($cart_items)): ?>
        <div class="k-empty">
            <svg class="k-empty__icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 4h2.2l2.1 10.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.5-1.1L20.5 8H6.2" />
                <circle cx="9.5" cy="19.5" r="1.4" />
                <circle cx="17" cy="19.5" r="1.4" />
            </svg>
            <h2>Your cart is empty</h2>
            <p>Looks like you haven't added any books yet.</p>
            <a href="shop.php" class="k-btn k-btn--primary">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="k-cartlayout">
            <form method="POST" action="cart.php" class="k-cartlayout__main">
                <div class="k-tablecard">
                    <table class="k-table k-table--stack">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="k-cartbook">
                                            <?php k_cover($item, 'k-thumb', false); ?>
                                            <div>
                                                <div class="k-cartbook__title"><?php echo htmlspecialchars($item['title']); ?></div>
                                                <div class="k-cartbook__author"><?php echo htmlspecialchars($item['author']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="k-num" data-label="Price">Rs. <?php echo number_format($item['price']); ?></td>
                                    <td data-label="Quantity">
                                        <input type="number" name="qty[<?php echo (int) $item['id']; ?>]"
                                            value="<?php echo (int) $item['qty']; ?>" min="1" class="k-input k-input--qty"
                                            aria-label="Quantity for <?php echo htmlspecialchars($item['title']); ?>">
                                    </td>
                                    <td class="k-num k-num--strong" data-label="Total">Rs.
                                        <?php echo number_format($item['price'] * $item['qty']); ?>
                                    </td>
                                    <td>
                                        <a href="cart.php?remove=<?php echo (int) $item['id']; ?>" class="k-remove"
                                            aria-label="Remove <?php echo htmlspecialchars($item['title']); ?> from cart">Remove</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="k-cartlayout__actions">
                    <button type="submit" name="update_cart" class="k-btn k-btn--outline">Update Cart</button>
                    <a href="shop.php" class="k-more">Continue Shopping
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" />
                        </svg>
                    </a>
                </div>
            </form>

            <aside class="k-summary">
                <div class="k-summary__row">
                    <span class="k-summary__label">Total</span>
                    <span class="k-summary__amount">Rs. <?php echo number_format($total_price); ?></span>
                </div>
                <a href="checkout.php" class="k-btn k-btn--primary k-btn--block">Proceed to Checkout</a>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
