<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Looked up before the header is output, so the tab title is correct and login redirects work.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$is_admin = ($_SESSION['user_role'] ?? '') === 'admin' ? 1 : 0;
$order_id = (int) ($_GET['id'] ?? 0);

// A customer can only open their own orders; an admin can open any
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND (user_id = ? OR ? = 1)");
$stmt->bind_param("iii", $order_id, $user_id, $is_admin);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    http_response_code(404);
    $page_title = 'Order not found';
    require_once '../includes/header.php';
    ?>
    <div class="k-page">
        <div class="k-empty">
            <p>We couldn't find that order.</p>
            <a href="profile.php" class="k-btn k-btn--outline">Back to My Account</a>
        </div>
    </div>
    <?php
    require_once '../includes/footer.php';
    exit();
}

$stmt = $conn->prepare(
    "SELECT oi.quantity, oi.price, b.id AS book_id, b.title, b.author, b.image
     FROM order_items oi LEFT JOIN books b ON b.id = oi.book_id
     WHERE oi.order_id = ? ORDER BY oi.id ASC"
);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$subtotal = 0;
foreach ($items as $it) {
    $subtotal += $it['price'] * $it['quantity'];
}
$extra = round($order['total'] - $subtotal, 2); // whatever the saved total includes beyond the books

$status = strtolower((string) $order['status']);
if (!in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
    $status = 'pending';
}
$steps = ['pending' => 'Order placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
$current_step = array_search($status, array_keys($steps), true);

$name = trim((string) ($order['name'] ?? ''));
$email = trim((string) ($order['email'] ?? ''));
$address = k_order_address($order);

$page_title = 'Order #' . $order['id'];
require_once '../includes/header.php';
?>

<div class="k-page k-page--stack">
    <a href="profile.php" class="k-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5M11 6l-6 6 6 6" />
        </svg>
        My Account
    </a>

    <div class="k-od__head">
        <h1 class="k-h1">Order #<?php echo (int) $order['id']; ?></h1>
        <span class="k-pill k-pill--<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
        <span class="k-od__date">Placed <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></span>
    </div>

    <div class="k-cartlayout">
        <div class="k-cartlayout__main">
            <div class="k-tablecard">
                <table class="k-table k-table--stack">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <?php
                            $title = $it['title'] ?? 'Book no longer available';
                            $book = ['title' => $title, 'image' => $it['image'] ?? ''];
                            ?>
                            <tr>
                                <td>
                                    <div class="k-cartbook">
                                        <?php k_cover($book, 'k-thumb', false); ?>
                                        <div>
                                            <?php if ($it['book_id']): ?>
                                                <a class="k-cartbook__title k-od__booklink"
                                                    href="product.php?id=<?php echo (int) $it['book_id']; ?>"><?php echo htmlspecialchars($title); ?></a>
                                            <?php else: ?>
                                                <div class="k-cartbook__title"><?php echo htmlspecialchars($title); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($it['author'])): ?>
                                                <div class="k-cartbook__author"><?php echo htmlspecialchars($it['author']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="k-num" data-label="Price">Rs. <?php echo number_format($it['price']); ?></td>
                                <td class="k-num" data-label="Quantity"><?php echo (int) $it['quantity']; ?></td>
                                <td class="k-num k-num--strong" data-label="Total">Rs.
                                    <?php echo number_format($it['price'] * $it['quantity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="4" class="k-table__empty">No items were saved for this order.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <aside class="k-summary">
            <section>
                <h2 class="k-od__label">Delivery</h2>
                <p class="k-od__text">
                    <?php if ($name !== ''): ?><strong><?php echo htmlspecialchars($name); ?></strong><br><?php endif; ?>
                    <?php echo $address !== '' ? nl2br(htmlspecialchars($address)) : 'No address saved'; ?>
                    <?php if ($email !== ''): ?><br><span class="k-od__muted"><?php echo htmlspecialchars($email); ?></span><?php endif; ?>
                </p>
            </section>

            <section>
                <h2 class="k-od__label">Payment</h2>
                <p class="k-od__text"><?php echo htmlspecialchars(k_payment_label($order['payment_method'] ?? '')); ?></p>
            </section>

            <section>
                <h2 class="k-od__label">Status</h2>
                <?php if ($status === 'cancelled'): ?>
                    <div class="k-alert k-alert--error">This order was cancelled.</div>
                <?php else: ?>
                    <ol class="k-steps">
                        <?php $i = 0;
                        foreach ($steps as $label): ?>
                            <li class="k-steps__item<?php echo $i < $current_step ? ' is-done' : ($i === $current_step ? ' is-current' : ''); ?>"
                                <?php echo $i === $current_step ? 'aria-current="step"' : ''; ?>><?php echo $label; ?></li>
                            <?php $i++; endforeach; ?>
                    </ol>
                <?php endif; ?>
            </section>

            <div class="k-od__totals">
                <?php if ($extra > 0): ?>
                    <div class="k-od__line"><span>Items</span><span>Rs. <?php echo number_format($subtotal); ?></span></div>
                    <div class="k-od__line"><span>Delivery &amp; other charges</span><span>Rs. <?php echo number_format($extra); ?></span></div>
                <?php endif; ?>
                <div class="k-summary__row k-summary__row--total">
                    <span class="k-summary__label">Total</span>
                    <span class="k-summary__amount">Rs. <?php echo number_format($order['total']); ?></span>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
