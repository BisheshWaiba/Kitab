<?php
mysqli_report(MYSQLI_REPORT_OFF); // Disable auto-exceptions for manual handling
require_once '../config/db.php';

echo "<h1>Database Fixer</h1>";

// 1. Check if table exists
$check_table = $conn->query("SHOW TABLES LIKE 'users'");
if ($check_table->num_rows == 0) {
    die("<p style='color:red'>Error: Table 'users' does not exist! Please run setup.php first.</p>");
}
echo "<p>Table 'users' exists.</p>";

// 2. Check if column exists
$check_col = $conn->query("SHOW COLUMNS FROM users LIKE 'favorite_genres'");
if ($check_col->num_rows > 0) {
    echo "<p style='color:green'>Column 'favorite_genres' ALREADY exists. You should be fine.</p>";
} else {
    echo "<p>Column 'favorite_genres' missing. Attempting to add...</p>";

    // 3. Add Column
    $sql = "ALTER TABLE users ADD favorite_genres TEXT";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Success! Column 'favorite_genres' added.</p>";
    } else {
        echo "<p style='color:red'>Failed to add column: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "1. <a href='preferences.php'>Click here to try Genre Selection again</a>";
?>