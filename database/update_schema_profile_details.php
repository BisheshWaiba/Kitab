<?php
// Adds the optional account details (date of birth, gender, city) to the users table.
// Safe to run more than once. The Edit profile and account pages also do this by themselves when a column is missing.
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';
require_once '../includes/helpers.php';

echo "<h1>Database Update: Account Details</h1>";

k_user_columns($conn);

foreach (['date_of_birth', 'gender', 'city'] as $column) {
    $found = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($found && $found->num_rows > 0) {
        echo "<p style='color:green'>Column '$column' is in the 'users' table.</p>";
    } else {
        echo "<p style='color:red'>Could not add the column '$column': " . htmlspecialchars($conn->error) . "</p>";
    }
}

echo "<hr>";
echo "<a href='../frontend/profile.php'>Go to your account</a>";
?>
