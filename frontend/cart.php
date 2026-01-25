<?php
require_once '../config/db.php';
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
?>

<div class="container container-padding" style="padding: 4rem 2rem;">
    <h1>Shopping Cart</h1>

    <?php if (empty($cart_items)): ?>
        <div class="empty-state">
            <i class="fas fa-shopping-basket" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
            <h2>Your cart is empty</h2>
            <p style="margin-bottom: 2rem;">Looks like you haven't added any books yet.</p>
            <a href="shop.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php else: ?>
        <form method="POST" action="cart.php">
            <div style="overflow-x: auto;">
                <table class="cart-table">
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
                                    <div style="display:flex; align-items:center; gap:1rem;">
                                        <img src="<?php echo strpos($item['image'], 'http') === 0 ? htmlspecialchars($item['image']) : BASE_URL . htmlspecialchars($item['image']); ?>" alt="img"
                                            style="width:60px; height:80px; object-fit:cover;">
                                        <div>
                                            <h4 style="font-size:1rem; margin:0;">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </h4>
                                            <p style="font-size:0.9rem; color:#64748b; margin:0;">
                                                <?php echo htmlspecialchars($item['author']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>Rs. <?php echo number_format($item['price']); ?></td>
                                <td>
                                    <input type="number" name="qty[<?php echo $item['id']; ?>]"
                                        value="<?php echo $item['qty']; ?>" min="1" class="form-control"
                                        style="width:70px; padding:0.5rem;">
                                </td>
                                <td style="font-weight:700; color:var(--primary);">Rs.
                                    <?php echo number_format($item['price'] * $item['qty']); ?>
                                </td>
                                <td>
                                    <a href="cart.php?remove=<?php echo $item['id']; ?>" style="color:#ef4444;"><i
                                            class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div
                style="display:flex; justify-content:space-between; align-items:center; margin-top:2rem; background:#f8fafc; padding:2rem; border-radius:12px;">
                <div>
                    <button type="submit" name="update_cart" class="btn btn-outline">Update Cart</button>
                    <a href="shop.php" class="btn btn-outline" style="border:none;">Continue Shopping</a>
                </div>
                <div style="text-align:right;">
                    <h3 style="margin-bottom:1rem; font-size:1.5rem;">Total: Rs.
                        <?php echo number_format($total_price); ?>
                    </h3>
                    <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>