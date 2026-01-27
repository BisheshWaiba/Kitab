<?php
require_once 'includes/header.php';

$success = '';
$error = '';

$user_id = $_SESSION['user_id'];

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Update Name & Email
    $sql = "UPDATE users SET name = '$name', email = '$email' WHERE id = $user_id";

    // Update Password if provided
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET name = '$name', email = '$email', password = '$hashed_password' WHERE id = $user_id";
    }

    if ($conn->query($sql) === TRUE) {
        $success = "Profile updated successfully!";
        $_SESSION['user_name'] = $name; // Update session
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
}

// Fetch Current User Data
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
?>

<main class="main-content">
    <header class="admin-header">
        <h1>My Profile</h1>
    </header>

    <div class="admin-card" style="max-width: 800px;">
        <?php if ($success): ?>
            <div class="alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <label class="form-label">New Password <span style="font-weight:400; color:#64748b; font-size:0.9em;">(leave blank to keep current)</span></label>
                <input type="password" name="password" class="form-input">
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-admin btn-admin-primary btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</main>

</body>

</html>