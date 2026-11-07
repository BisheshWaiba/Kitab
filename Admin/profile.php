<?php
require_once 'includes/header.php';

$success = '';
$error = '';

$user_id = (int) $_SESSION['user_id'];

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $raw_name = trim($_POST['name'] ?? '');
    $raw_email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($raw_name === '' || $raw_email === '') {
        $error = "Please enter your name and email address.";
    } elseif (!filter_var($raw_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $name = $conn->real_escape_string($raw_name);
        $email = $conn->real_escape_string($raw_email);

        // Emails are unique: check first so the admin gets a clear message instead of a database error
        $taken = $conn->query("SELECT id FROM users WHERE email = '$email' AND id <> $user_id");
        if ($taken && $taken->num_rows > 0) {
            $error = "That email address is already used by another account.";
        } else {
            // Update Name & Email
            $sql = "UPDATE users SET name = '$name', email = '$email' WHERE id = $user_id";

            // Update Password if provided
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET name = '$name', email = '$email', password = '$hashed_password' WHERE id = $user_id";
            }

            if ($conn->query($sql) === TRUE) {
                $success = "Profile updated successfully!";
                $_SESSION['user_name'] = $raw_name; // Update session
            } else {
                $error = "Something went wrong updating your profile. Please try again.";
            }
        }
    }
}

// Fetch Current User Data
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// After a failed save keep what was typed; otherwise show the saved values
$form_name = $error ? ($_POST['name'] ?? '') : $user['name'];
$form_email = $error ? ($_POST['email'] ?? '') : $user['email'];
?>

<main class="k-admin__main">
    <div class="k-admin__bar">
        <h1 class="k-h1">My Profile</h1>
    </div>

    <div class="k-formstack">
        <?php if ($success): ?>
            <div class="k-alert k-alert--success" role="status"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="k-card k-formcard">
            <div class="k-fields">
                <div class="k-field">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="k-input" required autocomplete="name"
                        value="<?php echo htmlspecialchars((string) $form_name); ?>">
                </div>
                <div class="k-field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="k-input" required autocomplete="email"
                        value="<?php echo htmlspecialchars((string) $form_email); ?>">
                </div>
            </div>

            <div class="k-field">
                <label for="password">New Password <span class="k-field__hint">(leave blank to keep current)</span></label>
                <input type="password" id="password" name="password" class="k-input" autocomplete="new-password">
            </div>

            <button type="submit" class="k-btn k-btn--primary k-btn--block">Save Changes</button>
        </form>
    </div>
</main>

</body>

</html>
