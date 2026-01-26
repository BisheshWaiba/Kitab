<?php include 'includes/header.php';

// Fetch Orders
$orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
?>

<main class="main-content">
    <header class="admin-header">
        <h1>Order Monitoring</h1>
    </header>

    <div class="admin-card">
        <table class="admin-table">
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
                    <tr>
                        <td>#
                            <?php echo $order['id']; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($order['name']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?>
                        </td>
                        <td>Rs.
                            <?php echo number_format($order['total']); ?>/
                        </td>
                        <td>
                            <?php echo htmlspecialchars($order['payment_method'] ?? 'COD'); ?>
                        </td>
                        <td>
                            <span class="status-pills status-pending">Processing</span>
                        </td>
                        <td>
                            <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($orders->num_rows == 0): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: #64748b;">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>

</html>