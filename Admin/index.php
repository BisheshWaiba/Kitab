<?php include 'includes/header.php';
require_once dirname(__DIR__) . '/includes/helpers.php'; // for k_cover()

// Fetch Stats
$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
// Revenue leaves out cancelled orders: that money never comes in
$total_revenue = $conn->query("SELECT SUM(total) as sum FROM orders WHERE status <> 'cancelled'")->fetch_assoc()['sum'] ?? 0;

// What is waiting for the admin
$pending_orders = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetch_row()[0];
$soldout_books = (int) $conn->query("SELECT COUNT(*) FROM books WHERE stock <= 0")->fetch_row()[0];
$low_books = (int) $conn->query("SELECT COUNT(*) FROM books WHERE stock BETWEEN 1 AND 5")->fetch_row()[0];

// Orders per day for the last 7 days (today last), cancelled ones left out; days with no orders count as 0
$per_day = [];
foreach ($conn->query("SELECT DATE(created_at) AS d, COUNT(*) AS n, SUM(total) AS s FROM orders WHERE status <> 'cancelled' AND created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY d")->fetch_all(MYSQLI_ASSOC) as $row) {
    $per_day[$row['d']] = [(int) $row['n'], (float) $row['s']];
}
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $key = date('Y-m-d', strtotime("-$i days"));
    [$n, $sum] = $per_day[$key] ?? [0, 0.0];
    $days[] = ['key' => $key, 'label' => $i === 0 ? 'Today' : date('D', strtotime($key)), 'n' => $n, 'sum' => $sum, 'today' => $i === 0];
}
$max_day = max(1, max(array_column($days, 'n')));
$week_orders = array_sum(array_column($days, 'n'));
$week_revenue = array_sum(array_column($days, 'sum'));

// The books that sold most (copies in orders that were not cancelled)
$best = $conn->query("SELECT b.id, b.title, b.image, SUM(oi.quantity) AS sold FROM order_items oi JOIN orders o ON o.id = oi.order_id AND o.status <> 'cancelled' JOIN books b ON b.id = oi.book_id GROUP BY b.id ORDER BY sold DESC, b.id DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

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
    'Running low' => [$low_books, '5 copies or fewer left', 'books.php?stock=low'],
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

    <div class="k-dash2">
        <section class="k-card k-dashcard" aria-labelledby="k-week-title">
            <div class="k-dashcard__head">
                <h2 class="k-h3" id="k-week-title">Last 7 days</h2>
                <span class="k-dashcard__sub"><?php echo $week_orders . ($week_orders === 1 ? ' order' : ' orders'); ?> &middot; Rs. <?php echo number_format($week_revenue); ?></span>
            </div>
            <div class="k-chart" role="img" aria-label="Orders per day: <?php echo htmlspecialchars(implode(', ', array_map(fn($d) => $d['label'] . ' ' . $d['n'], $days))); ?>">
                <?php foreach ($days as $d): ?>
                    <div class="k-chart__day<?php echo $d['n'] > 0 ? ' has-orders' : ''; ?><?php echo $d['today'] ? ' is-today' : ''; ?>"
                        title="<?php echo date('M j', strtotime($d['key'])) . ': ' . $d['n'] . ($d['n'] === 1 ? ' order' : ' orders') . ', Rs. ' . number_format($d['sum']); ?>">
                        <span class="k-chart__n"><?php echo $d['n']; ?></span>
                        <span class="k-chart__bar" style="--h: <?php echo round($d['n'] / $max_day * 78); ?>%"></span>
                        <span class="k-chart__label"><?php echo $d['label']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="k-card k-dashcard" aria-labelledby="k-best-title">
            <div class="k-dashcard__head">
                <h2 class="k-h3" id="k-best-title">Best sellers</h2>
                <span class="k-dashcard__sub">copies sold</span>
            </div>
            <?php if ($best): ?>
                <ol class="k-top">
                    <?php foreach ($best as $i => $b): ?>
                        <li>
                            <span class="k-top__rank"><?php echo $i + 1; ?></span>
                            <?php k_cover($b, 'k-thumb', false); ?>
                            <span class="k-top__title"><?php echo htmlspecialchars($b['title']); ?></span>
                            <span class="k-top__n"><?php echo (int) $b['sold']; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="k-dashcard__empty">Nothing has sold yet. The top five will show here.</p>
            <?php endif; ?>
        </section>
    </div>

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
