<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';

echo "<h1>Database Update: Profile Picture</h1>";

// Check if column exists
$check_col = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
if ($check_col->num_rows == 0) {
    // Add Column
    $sql = "ALTER TABLE users ADD profile_picture VARCHAR(255) DEFAULT NULL";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Success! Column 'profile_picture' added to 'users' table.</p>";
    } else {
        echo "<p style='color:red'>Failed to add column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green'>Column 'profile_picture' already exists.</p>";
}

echo "<hr>";
echo "<p>Database ready. You can close this page.</p>";
echo "<a href='preferences.php'>Go to Setup Page</a>";
?>