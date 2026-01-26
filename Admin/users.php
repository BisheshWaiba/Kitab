<?php include 'includes/header.php';

// Fetch Users
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<main class="main-content">
    <header class="admin-header">
        <h1>User Management</h1>
    </header>

    <div class="admin-card">
        <table class="admin-table">
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
                    <tr>
                        <td>#
                            <?php echo $user['id']; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($user['name']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                        </td>
                        <td>
                            <span class="status-pills status-success">User</span>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($users->num_rows == 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: #64748b;">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>

</html>