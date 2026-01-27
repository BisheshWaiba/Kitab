<?php include 'includes/header.php';

// Fetch Stats
$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total) as sum FROM orders")->fetch_assoc()['sum'] ?? 0;

// Fetch Recent Orders
$recent_orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
?>

<main class="main-content">
    <header class="admin-header">
        <h1>Dashboard Overview</h1>
        <div class="admin-user">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
        </div>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-info">
                <h3>Total Books</h3>
                <div class="value">
                    <?php echo $total_books; ?>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-purple">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3>Total Users</h3>
                <div class="value">
                    <?php echo $total_users; ?>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-orange">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="stat-info">
                <h3>Total Orders</h3>
                <div class="value">
                    <?php echo $total_orders; ?>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-green">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-info">
                <h3>Revenue</h3>
                <div class="value">Rs.
                    <?php echo number_format($total_revenue); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="card-header">
            <h2>Recent Orders</h2>
            <a href="orders.php" class="btn-admin btn-admin-primary">View All</a>
        </div>
        <table class="admin-table">
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
                    <tr>
                        <td>#
                            <?php echo $order['id']; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($order['name']); ?>
                        </td>
                        <td>Rs.
                            <?php echo number_format($order['total']); ?>
                        </td>
                        <td>
                            <span class="status-pills status-success">Completed</span>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($recent_orders->num_rows == 0): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem; color: #64748b;">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>

</html>