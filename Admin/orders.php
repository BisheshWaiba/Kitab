<?php
// Order monitoring. Changing an order's status is handled before the header is output (it answers with a redirect).
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int) ($_POST['order_id'] ?? 0);
    $new = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $old = strtolower((string) ($row['status'] ?? ''));

    if (!k_csrf_valid($_POST['csrf'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Your session expired, so nothing was changed. Please try again.'];
    } elseif (!$row || !in_array($new, $statuses, true)) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'That order or status is not valid.'];
    } elseif ($old === 'cancelled' && $new !== 'cancelled') {
        // The copies of a cancelled order went back on the shelf (below), so it cannot be brought back to life
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Order #' . $order_id . ' was cancelled and cannot be reopened.'];
    } else {
        if ($new === 'cancelled' && $old !== 'cancelled') {
            // Cancelling puts the books back in stock
            $lines = [];
            $items = $conn->query("SELECT book_id, quantity FROM order_items WHERE order_id = " . $order_id);
            foreach ($items ? $items->fetch_all(MYSQLI_ASSOC) : [] as $it) {
                $lines[(int) $it['book_id']] = ($lines[(int) $it['book_id']] ?? 0) + (int) $it['quantity'];
            }
            k_return_stock($conn, $lines);
        }
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new, $order_id);
        $stmt->execute();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order #' . $order_id . ' is now ' . $new . ($new === 'cancelled' ? ', and its books are back in stock.' : '.')];
    }
    header("Location: " . BASE_URL . "admin/orders.php");
    exit();
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

include 'includes/header.php';

// Fetch Orders
$orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
?>

<main class="k-admin__main">
    <div class="k-admin__bar">
        <h1 class="k-h1">Order Monitoring</h1>
    </div>

    <?php if ($flash): ?>
        <div class="k-alert k-alert--<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>"
            role="<?php echo $flash['type'] === 'success' ? 'status' : 'alert'; ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
    <?php endif; ?>

    <div class="k-tablecard">
        <table class="k-table k-table--xwide">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Total Price</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <?php
                    $status = strtolower((string) $order['status']);
                    if (!in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
                        $status = 'pending';
                    }
                    // Readable payment method; an empty value means the default (cash on delivery)
                    $payment = (string) ($order['payment_method'] ?? '');
                    $payment_label = ($payment === '' || $payment === 'cash_on_delivery')
                        ? 'Cash on Delivery'
                        : ucwords(str_replace('_', ' ', $payment));
                    ?>
                    <tr>
                        <td class="k-num k-num--strong">#<?php echo (int) $order['id']; ?></td>
                        <td><?php echo htmlspecialchars((string) $order['name'] !== '' ? (string) $order['name'] : '—'); ?></td>
                        <td class="k-muted k-nowrap"><?php echo htmlspecialchars((string) $order['email'] !== '' ? (string) $order['email'] : '—'); ?></td>
                        <td class="k-num k-num--strong">Rs. <?php echo number_format($order['total']); ?></td>
                        <td class="k-muted"><?php echo htmlspecialchars($payment_label); ?></td>
                        <td>
                            <span class="k-pill k-pill--<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                            <?php if ($status !== 'cancelled'): ?>
                                <form method="POST" class="k-rowactions">
                                    <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                                    <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                                    <select name="status" class="k-input" aria-label="Status of order #<?php echo (int) $order['id']; ?>">
                                        <?php foreach ($statuses as $s): ?>
                                            <option value="<?php echo $s; ?>"<?php echo $s === $status ? ' selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="k-btn k-btn--outline k-btn--xs">Save</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="k-num k-muted"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($orders->num_rows == 0): ?>
                    <tr>
                        <td colspan="7" class="k-table__empty">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>

</html>
