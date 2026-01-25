<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';

echo "<h1>Database Update: Order Phone Number</h1>";

// Check if phone column exists in orders
$check_col = $conn->query("SHOW COLUMNS FROM orders LIKE 'phone'");
if ($check_col->num_rows == 0) {
    // Add Column
    $sql = "ALTER TABLE orders ADD phone VARCHAR(20) AFTER email";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Success! Column 'phone' added to 'orders' table.</p>";
    } else {
        echo "<p style='color:red'>Failed to add column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green'>Column 'phone' already exists in 'orders'.</p>";
}

echo "<hr>";
echo "<a href='../frontend/checkout.php'>Go back to Checkout</a>";
?>