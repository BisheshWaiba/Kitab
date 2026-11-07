<?php include 'includes/header.php';

// Fetch Orders
$orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
?>

<main class="k-admin__main">
    <div class="k-admin__bar">
        <h1 class="k-h1">Order Monitoring</h1>
    </div>

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
                        <td><span class="k-pill k-pill--<?php echo $status; ?>"><?php echo ucfirst($status); ?></span></td>
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
