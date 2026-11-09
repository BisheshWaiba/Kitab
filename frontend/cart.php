<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// The Cart page is flat (white and light-grey surfaces, from the design canvas); only its buttons are glass.
// is-gbtn switches the glass buttons on, and these stylesheets are loaded by the redesigned pages only
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/cart-flat.css'];
$page_title = 'Shopping Cart';
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
    foreach ((array) ($_POST['qty'] ?? []) as $id => $qty) {
        $id = intval($id);
        if (!isset($_SESSION['cart'][$id])) {
            continue; // only books already in the cart can change (a made-up id must not add a phantom item)
        }
        $qty = min(100, intval($qty)); // the Product page allows 100 at most, so does the cart
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

// The shop's promise ("Free shipping on orders over Rs. 2,000") shown as progress, and books that would complete it
$free_at = 2000;
$left = max(0, $free_at - $total_price);
$ship_pct = min(100, (int) round($total_price / $free_at * 100));
$item_count = array_sum(array_column($cart_items, 'qty'));
$suggestions = [];
if ($cart_items && $left > 0) {
    $in_cart = implode(',', array_map('intval', array_column($cart_items, 'id')));
    $stmt = $conn->prepare("SELECT * FROM books WHERE id NOT IN ($in_cart) AND price >= ? ORDER BY RAND() LIMIT 3");
    $stmt->bind_param("d", $left);
    $stmt->execute();
    $suggestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<main class="fl">

<section class="gc-head fl-wrap">
    <h1 class="fl-h1">Shopping Cart</h1>
    <?php if (!empty($cart_items)) { k_checkout_steps('cart'); } ?>
</section>

<div class="fl-wrap gc-main">
    <?php if (empty($cart_items)): ?>
        <?php k_empty_state('cart', 'Your cart is empty', "Looks like you haven't added any books yet.", 'Start Shopping', 'shop.php'); ?>
    <?php else: ?>
        <div class="gc-cart">
            <div class="gc-left">
                <form method="POST" action="cart.php" class="gc-list fl-panel" data-cart-form>
                    <input type="hidden" name="update_cart" value="1">
                    <ul>
                        <?php foreach ($cart_items as $item): ?>
                            <?php $href = 'product.php?id=' . (int) $item['id']; ?>
                            <li class="gc-row">
                                <a class="gc-row__cover" href="<?php echo $href; ?>" tabindex="-1" aria-hidden="true">
                                    <?php k_cover($item, 'gc-row__img', false); ?>
                                </a>
                                <div class="gc-row__info">
                                    <a class="gc-row__title" href="<?php echo $href; ?>"><?php echo htmlspecialchars($item['title']); ?></a>
                                    <span class="gc-row__by"><?php echo htmlspecialchars($item['author']); ?></span>
                                    <a href="cart.php?remove=<?php echo (int) $item['id']; ?>" class="gc-remove k-btn k-btn--ghost k-btn--sm"
                                        aria-label="Remove <?php echo htmlspecialchars($item['title']); ?> from cart">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12h10l1-12M9 7V4h6v3" />
                                        </svg>
                                        Remove
                                    </a>
                                </div>
                                <div class="gp-qty" data-qty role="group"
                                    aria-label="Quantity for <?php echo htmlspecialchars($item['title']); ?>">
                                    <button type="button" class="gp-qty__btn" data-step="-1" aria-label="Decrease quantity">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                            <path d="M5 12h14" />
                                        </svg>
                                    </button>
                                    <input type="number" name="qty[<?php echo (int) $item['id']; ?>]"
                                        value="<?php echo (int) $item['qty']; ?>" min="1" max="100"
                                        aria-label="Quantity for <?php echo htmlspecialchars($item['title']); ?>">
                                    <button type="button" class="gp-qty__btn" data-step="1" aria-label="Increase quantity">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                            <path d="M12 5v14M5 12h14" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="gc-row__price">Rs. <?php echo number_format($item['price'] * $item['qty']); ?>
                                    <?php if ((int) $item['qty'] > 1): ?>
                                        <small>Rs. <?php echo number_format($item['price']); ?> each</small>
                                    <?php endif; ?>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <noscript><button type="submit" class="k-btn k-btn--outline k-btn--sm">Update cart</button></noscript>
                </form>

                <?php if ($suggestions): ?>
                    <section class="gc-suggest fl-panel" aria-labelledby="gc-suggest">
                        <h2 class="fl-h2" id="gc-suggest">Add a book, ship it free</h2>
                        <p class="fl-sub">Any of these takes you past Rs. 2,000.</p>
                        <ul class="gc-minis">
                            <?php foreach ($suggestions as $s): ?>
                                <li>
                                    <a href="product.php?id=<?php echo (int) $s['id']; ?>" class="gc-mini">
                                        <?php k_cover($s, 'gc-mini__img'); ?>
                                        <span>
                                            <strong><?php echo htmlspecialchars($s['title']); ?></strong>
                                            <small>Rs. <?php echo number_format($s['price']); ?></small>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="gc-summary fl-panel" aria-label="Order summary">
                <h2 class="fl-h2">Order Summary</h2>
                <div class="gc-ship">
                    <?php if ($left > 0): ?>
                        <span><strong>Rs. <?php echo number_format($left); ?></strong> more for free shipping</span>
                    <?php else: ?>
                        <span><strong>You've unlocked free shipping</strong></span>
                    <?php endif; ?>
                    <span class="gc-ship__track" aria-hidden="true"><span class="gc-ship__fill"
                            style="width: <?php echo $ship_pct; ?>%"></span></span>
                </div>
                <div class="gc-summary__row"><span>Items (<?php echo (int) $item_count; ?>)</span><span>Rs. <?php echo number_format($total_price); ?></span></div>
                <div class="gc-summary__total"><span>Total</span><strong>Rs. <?php echo number_format($total_price); ?></strong></div>
                <a href="checkout.php" class="k-btn k-btn--primary k-btn--block">Proceed to Checkout
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </a>
                <a href="shop.php" class="k-btn k-btn--outline k-btn--block">Continue Shopping</a>
                <p class="gc-summary__note">You pay in cash when your order arrives.</p>
            </aside>
        </div>
    <?php endif; ?>
</div>

</main>

<?php require_once '../includes/footer.php'; ?>
