<?php include 'includes/header.php';

// Fetch Stats
$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total) as sum FROM orders")->fetch_assoc()['sum'] ?? 0;

// Fetch Recent Orders
$recent_orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");

$stats = [
    'Total Books' => $total_books,
    'Total Users' => $total_users,
    'Total Orders' => $total_orders,
    'Revenue' => 'Rs. ' . number_format($total_revenue),
];
?>

<main class="k-admin__main">
    <div class="k-admin__bar">
        <h1 class="k-h1">Dashboard Overview</h1>
        <span class="k-admin__welcome">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
    </div>

    <div class="k-stats">
        <?php foreach ($stats as $label => $value): ?>
            <div class="k-card k-stat">
                <h2 class="k-stat__label"><?php echo $label; ?></h2>
                <div class="k-stat__value"><?php echo $value; ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="k-card k-admincard">
        <div class="k-admincard__head">
            <h2 class="k-h3">Recent Orders</h2>
            <a href="orders.php" class="k-btn k-btn--primary k-btn--sm">View All</a>
        </div>
        <div class="k-tablecard">
            <table class="k-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <?php
                        $status = strtolower((string) $order['status']);
                        if (!in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
                            $status = 'pending';
                        }
                        ?>
                        <tr>
                            <td class="k-num k-num--strong">#<?php echo (int) $order['id']; ?></td>
                            <td><?php echo htmlspecialchars((string) $order['name'] !== '' ? (string) $order['name'] : '—'); ?></td>
                            <td class="k-num">Rs. <?php echo number_format($order['total']); ?></td>
                            <td><span class="k-pill k-pill--<?php echo $status; ?>"><?php echo ucfirst($status); ?></span></td>
                            <td class="k-muted"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($recent_orders->num_rows == 0): ?>
                        <tr>
                            <td colspan="5" class="k-table__empty">No orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

</body>

</html>
