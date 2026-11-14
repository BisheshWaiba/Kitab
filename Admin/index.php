<?php include 'includes/header.php';

// Fetch Stats
$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
// Revenue leaves out cancelled orders: that money never comes in
$total_revenue = $conn->query("SELECT SUM(total) as sum FROM orders WHERE status <> 'cancelled'")->fetch_assoc()['sum'] ?? 0;

// What is waiting for the admin
$pending_orders = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetch_row()[0];
$soldout_books = (int) $conn->query("SELECT COUNT(*) FROM books WHERE stock <= 0")->fetch_row()[0];

// Fetch Recent Orders
$recent_orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");

// label => [value, a line under it, the drawing of the icon]
$stats = [
    'Total Books' => [$total_books, $soldout_books > 0 ? $soldout_books . ' sold out' : 'All in stock', '<path d="M5 4h10a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3V4z" /><path d="M5 17a3 3 0 0 1 3-3h10" />'],
    'Total Users' => [$total_users, 'Customers and admins', '<circle cx="9" cy="8" r="3.5" /><path d="M2.5 20a6.5 6.5 0 0 1 13 0" /><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.3a6.5 6.5 0 0 1 3.5 5.7" />'],
    'Total Orders' => [$total_orders, $pending_orders > 0 ? $pending_orders . ' waiting' : 'None waiting', '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" /><path d="M9 8h6M9 12h6" />'],
    'Revenue' => ['Rs. ' . number_format($total_revenue), 'Cancelled orders not counted', '<rect x="3" y="6" width="18" height="12" rx="2" /><circle cx="12" cy="12" r="2.6" />'],
];

// label => [count, the words under it, where it leads]
$todo = [
    'Pending orders' => [$pending_orders, 'to get moving', 'orders.php?status=pending'],
    'Sold-out books' => [$soldout_books, 'to restock', 'books.php?stock=out'],
    'Unread messages' => [$unread_messages, 'from the Contact page', 'messages.php'],
];
?>

<main class="k-admin__main">
    <div class="k-admin__bar">
        <h1 class="k-h1">Dashboard Overview</h1>
        <span class="k-admin__welcome">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
    </div>

    <div class="k-stats">
        <?php foreach ($stats as $label => [$value, $hint, $icon]): ?>
            <div class="k-card k-stat">
                <span class="k-stat__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round"><?php echo $icon; ?></svg>
                </span>
                <h2 class="k-stat__label"><?php echo $label; ?></h2>
                <div class="k-stat__value"><?php echo $value; ?></div>
                <div class="k-stat__hint"><?php echo htmlspecialchars($hint); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <section aria-label="Needs a look">
        <div class="k-todo">
            <?php foreach ($todo as $label => [$count, $words, $href]): ?>
                <a href="<?php echo $href; ?>" class="k-todo__item<?php echo $count > 0 ? ' is-hot' : ''; ?>">
                    <span class="k-todo__n"><?php echo (int) $count; ?></span>
                    <span class="k-todo__text"><?php echo $label; ?><small><?php echo $words; ?></small></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="k-card k-admincard">
        <div class="k-admincard__head">
            <h2 class="k-h3">Recent Orders</h2>
            <a href="orders.php" class="k-btn k-btn--primary k-btn--sm">View All</a>
        </div>
        <div class="k-tablecard">
            <table class="k-table k-table--stack">
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
                            <td class="k-num k-num--strong"><a class="k-idlink" href="<?php echo BASE_URL; ?>frontend/order.php?id=<?php echo (int) $order['id']; ?>">#<?php echo (int) $order['id']; ?></a></td>
                            <td data-label="Customer"><?php echo htmlspecialchars((string) $order['name'] !== '' ? (string) $order['name'] : '—'); ?></td>
                            <td class="k-num" data-label="Total">Rs. <?php echo number_format($order['total']); ?></td>
                            <td data-label="Status"><span class="k-pill k-pill--<?php echo $status; ?>"><?php echo ucfirst($status); ?></span></td>
                            <td class="k-muted" data-label="Date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
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
