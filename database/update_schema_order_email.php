<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';

echo "<h1>Database Update: Order Email</h1>";

// Check if email column exists in orders
$check_col = $conn->query("SHOW COLUMNS FROM orders LIKE 'email'");
if ($check_col->num_rows == 0) {
    // Add Column
    $sql = "ALTER TABLE orders ADD email VARCHAR(255) AFTER name";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Success! Column 'email' added to 'orders' table.</p>";
    } else {
        echo "<p style='color:red'>Failed to add column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green'>Column 'email' already exists in 'orders'.</p>";
}

echo "<hr>";
echo "<a href='../frontend/checkout.php'>Go back to Checkout</a>";
?>