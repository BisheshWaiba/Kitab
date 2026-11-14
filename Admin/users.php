<?php include 'includes/header.php';

// What the admin asked for: words to look for (name, email or phone), and/or only admins or only customers
$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$role_filter = in_array($_GET['role'] ?? '', ['admin', 'user'], true) ? $_GET['role'] : '';

$where = [];
$types = '';
$args = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $types .= 'sss';
    array_push($args, $like, $like, $like);
}
if ($role_filter === 'admin') {
    $where[] = "u.role = 'admin'";
} elseif ($role_filter === 'user') {
    $where[] = "(u.role IS NULL OR u.role <> 'admin')";
}
$stmt = $conn->prepare(
    'SELECT u.*, COUNT(o.id) AS order_count FROM users u LEFT JOIN orders o ON o.user_id = u.id'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' GROUP BY u.id ORDER BY u.id DESC'
);
if ($args) {
    $stmt->bind_param($types, ...$args);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// The numbers on the chips
[$count_all, $count_admin] = array_map('intval', $conn->query("SELECT COUNT(*), COALESCE(SUM(role = 'admin'), 0) FROM users")->fetch_row());
$count_user = $count_all - $count_admin;

// The address of this page with the role filter changed (the search words travel along)
$link = function (string $role) use ($q): string {
    $query = array_filter(['q' => $q, 'role' => $role], fn($v) => $v !== '');
    return 'users.php' . ($query ? '?' . htmlspecialchars(http_build_query($query), ENT_QUOTES) : '');
};
?>

<main class="k-admin__main">
    <div class="k-admin__bar">
        <h1 class="k-h1">User Management</h1>
        <span class="k-admin__welcome"><?php echo $count_all . ($count_all === 1 ? ' user' : ' users'); ?></span>
    </div>

    <div class="k-toolbar">
        <form action="users.php" method="GET" class="k-search" role="search">
            <?php if ($role_filter !== ''): ?><input type="hidden" name="role" value="<?php echo $role_filter; ?>"><?php endif; ?>
            <button type="submit" class="k-search__btn" aria-label="Search users">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
            </button>
            <input type="text" name="q" placeholder="Name, email or phone" aria-label="Search users" value="<?php echo htmlspecialchars($q); ?>">
        </form>
        <ul class="k-chips" aria-label="Filter by role">
            <li><a href="<?php echo $link(''); ?>" class="k-genre<?php echo $role_filter === '' ? ' is-active' : ''; ?>"<?php echo $role_filter === '' ? ' aria-current="true"' : ''; ?>>All<span class="k-chips__n"><?php echo $count_all; ?></span></a></li>
            <li><a href="<?php echo $link('user'); ?>" class="k-genre<?php echo $role_filter === 'user' ? ' is-active' : ''; ?>"<?php echo $role_filter === 'user' ? ' aria-current="true"' : ''; ?>>Customers<span class="k-chips__n"><?php echo $count_user; ?></span></a></li>
            <li><a href="<?php echo $link('admin'); ?>" class="k-genre<?php echo $role_filter === 'admin' ? ' is-active' : ''; ?>"<?php echo $role_filter === 'admin' ? ' aria-current="true"' : ''; ?>>Admins<span class="k-chips__n"><?php echo $count_admin; ?></span></a></li>
        </ul>
    </div>
    <?php if ($q !== '' || $role_filter !== ''): ?>
        <p class="k-admin__count"><?php echo count($users) . (count($users) === 1 ? ' user' : ' users') . ' of ' . $count_all; ?>
            &middot; <a href="users.php" class="k-idlink">Clear</a></p>
    <?php endif; ?>

    <div class="k-tablecard">
        <table class="k-table k-table--xwide k-table--stack">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Orders</th>
                    <th>Role</th>
                    <th>Registered At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php $is_admin = ($user['role'] ?? '') === 'admin'; ?>
                    <tr>
                        <td class="k-num k-muted" data-label="ID">#<?php echo (int) $user['id']; ?></td>
                        <td class="k-num--strong" data-label="Name"><?php echo htmlspecialchars((string) $user['name']); ?></td>
                        <td class="k-muted k-nowrap" data-label="Email"><?php echo htmlspecialchars((string) $user['email']); ?></td>
                        <td class="k-num k-muted" data-label="Phone"><?php echo htmlspecialchars((string) $user['phone'] !== '' ? (string) $user['phone'] : '—'); ?></td>
                        <td class="k-num" data-label="Orders"><?php echo (int) $user['order_count'] > 0 ? (int) $user['order_count'] : '<span class="k-muted">0</span>'; ?></td>
                        <td data-label="Role">
                            <?php if ($is_admin): ?>
                                <span class="k-pill k-pill--admin">Admin</span>
                            <?php else: ?>
                                <span class="k-pill k-pill--user">User</span>
                            <?php endif; ?>
                        </td>
                        <td class="k-num k-muted" data-label="Registered"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?>
                    <tr>
                        <td colspan="7" class="k-table__empty"><?php echo ($q !== '' || $role_filter !== '') ? 'No users match. <a href="users.php">Show everyone</a>.' : 'No users found.'; ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>

</html>
