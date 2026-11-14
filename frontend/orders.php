<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// My orders: every order of the logged-in customer, newest first, each with a progress line (placed, processing, shipped,
// delivered). The full order is on order.php. A flat page (white and light-grey surfaces); only its buttons are glass.
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/orders-flat.css'];
$page_title = 'My orders';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// The books in those orders: a few covers and the first titles on each card (a book that was deleted since still counts)
$order_books = [];
if ($orders) {
    $order_ids = implode(',', array_map(fn($o) => (int) $o['id'], $orders));
    $rows = $conn->query(
        "SELECT oi.order_id, oi.quantity, b.id AS book_id, b.title, b.image
         FROM order_items oi LEFT JOIN books b ON b.id = oi.book_id
         WHERE oi.order_id IN ($order_ids) ORDER BY oi.id ASC"
    );
    foreach ($rows ? $rows->fetch_all(MYSQLI_ASSOC) : [] as $r) {
        $r['title'] = $r['title'] ?? 'Book no longer available';
        $order_books[(int) $r['order_id']][] = $r;
    }
}

// The four steps of an order, and one line of plain words for where it stands
$steps = ['pending' => 'Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
$says = [
    'pending' => "We've received your order.",
    'processing' => "We're getting your books ready.",
    'shipped' => 'Your order is on its way.',
    'delivered' => 'Delivered. Enjoy the read!',
    'cancelled' => 'This order was cancelled.',
];
$plural = fn(int $n, string $one, string $many): string => $n . ' ' . ($n === 1 ? $one : $many);
?>

<main class="fl">
    <div class="fl-wrap po">
        <a href="profile.php" class="po-back k-btn k-btn--ghost k-btn--sm">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M11 6l-6 6 6 6" />
            </svg>
            My account
        </a>
        <h1 class="fl-h1">My orders</h1>
        <?php if ($orders): ?>
            <p class="fl-sub"><?php echo $plural(count($orders), 'order', 'orders'); ?>, newest first</p>

            <ul class="po-list">
                <?php foreach ($orders as $order): ?>
                    <?php
                    $oid = (int) $order['id'];
                    $status = strtolower((string) $order['status']);
                    if (!isset($says[$status])) {
                        $status = 'pending';
                    }
                    $books_in = $order_books[$oid] ?? [];
                    $book_count = array_sum(array_map(fn($b) => (int) $b['quantity'], $books_in));
                    $shown = array_slice($books_in, 0, 4);
                    $more = count($books_in) - count($shown);
                    $names = array_map(fn($b) => $b['title'], array_slice($books_in, 0, 2));
                    $rest = count($books_in) - count($names);
                    $step = array_search($status, array_keys($steps), true); // false for a cancelled order
                    $address = k_order_address($order);
                    ?>
                    <li class="po-order fl-panel">
                        <div class="po-top">
                            <div>
                                <a class="po-id" href="order.php?id=<?php echo $oid; ?>">Order #<?php echo $oid; ?></a>
                                <p class="po-meta"><?php echo date('F j, Y', strtotime($order['created_at'])); ?><?php echo $book_count > 0 ? ' · ' . $plural($book_count, 'book', 'books') : ''; ?></p>
                            </div>
                            <div class="po-top__side">
                                <span class="k-pill k-pill--<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                                <strong class="po-total">Rs. <?php echo number_format((float) $order['total']); ?></strong>
                            </div>
                        </div>

                        <?php if ($books_in): ?>
                            <div class="po-books">
                                <a class="po-covers" href="order.php?id=<?php echo $oid; ?>" tabindex="-1" aria-hidden="true">
                                    <?php foreach ($shown as $b): ?>
                                        <?php k_cover($b, 'po-cover', false); ?>
                                    <?php endforeach; ?>
                                    <?php if ($more > 0): ?><span class="po-more">+<?php echo $more; ?></span><?php endif; ?>
                                </a>
                                <p class="po-titles"><?php echo htmlspecialchars(implode(', ', $names)); ?><?php echo $rest > 0 ? ' and ' . $rest . ' more' : ''; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($step !== false): ?>
                            <ol class="po-track" aria-label="Where this order is">
                                <?php $i = 0; foreach ($steps as $label): ?>
                                    <li class="<?php echo $i < $step ? 'is-done' : ($i === $step ? 'is-current' : ''); ?>"
                                        <?php echo $i === $step ? 'aria-current="step"' : ''; ?>>
                                        <span class="po-track__dot" aria-hidden="true"></span>
                                        <span class="po-track__label"><?php echo $label; ?></span>
                                        <?php if ($i < $step): ?><span class="k-sr">(done)</span><?php endif; ?>
                                    </li>
                                    <?php $i++; endforeach; ?>
                            </ol>
                        <?php endif; ?>
                        <p class="po-say"><?php echo $says[$status]; ?></p>

                        <div class="po-foot">
                            <p class="po-ship"><?php echo $address !== '' ? 'To ' . htmlspecialchars($address) : ''; ?></p>
                            <a href="order.php?id=<?php echo $oid; ?>" class="k-btn k-btn--outline k-btn--sm"
                                aria-label="View details of order #<?php echo $oid; ?>">View details</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <?php k_empty_state('cart', 'No orders yet', 'When you place an order, you can follow it here.', 'Start Shopping', 'shop.php'); ?>
        <?php endif; ?>
    </div>
</main>

<?php
$hide_footer = true; // like the account page: it ends with its own content; the site script still loads
require_once '../includes/footer.php';
?>
