<?php include 'includes/header.php';

// Fetch Users
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<main class="k-admin__main">
    <div class="k-admin__bar">
        <h1 class="k-h1">User Management</h1>
    </div>

    <div class="k-tablecard">
        <table class="k-table k-table--xwide">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Registered At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <?php $is_admin = ($user['role'] ?? '') === 'admin'; ?>
                    <tr>
                        <td class="k-num k-muted">#<?php echo (int) $user['id']; ?></td>
                        <td class="k-num--strong"><?php echo htmlspecialchars((string) $user['name']); ?></td>
                        <td class="k-muted k-nowrap"><?php echo htmlspecialchars((string) $user['email']); ?></td>
                        <td class="k-num k-muted"><?php echo htmlspecialchars((string) $user['phone'] !== '' ? (string) $user['phone'] : '—'); ?></td>
                        <td>
                            <?php if ($is_admin): ?>
                                <span class="k-pill k-pill--admin">Admin</span>
                            <?php else: ?>
                                <span class="k-pill k-pill--user">User</span>
                            <?php endif; ?>
                        </td>
                        <td class="k-num k-muted"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($users->num_rows == 0): ?>
                    <tr>
                        <td colspan="6" class="k-table__empty">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>

</html>
