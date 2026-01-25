<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';

echo "<h1>Database Update: Seller Feature</h1>";

// Check if seller_id column exists
$check_col = $conn->query("SHOW COLUMNS FROM books LIKE 'seller_id'");
if ($check_col->num_rows == 0) {
    // Add Column
    $sql = "ALTER TABLE books ADD seller_id INT DEFAULT NULL";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Success! Column 'seller_id' added to 'books' table.</p>";
    } else {
        echo "<p style='color:red'>Failed to add column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green'>Column 'seller_id' already exists.</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<p>Database ready. You can close this page.</p>";
echo "<a href='profile.php'>Go back to Profile</a>";
?>