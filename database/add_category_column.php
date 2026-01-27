<?php
/**
 * Add missing category column to books table
 */

require_once dirname(__DIR__) . '/config/db.php';

echo "Checking books table structure...\n\n";

// Check if category column exists
$result = $conn->query("SHOW COLUMNS FROM books LIKE 'category'");

if ($result->num_rows == 0) {
    echo "→ Adding 'category' column to books table...\n";

    $sql = "ALTER TABLE books ADD COLUMN category VARCHAR(100) DEFAULT 'General' AFTER price";

    if ($conn->query($sql)) {
        echo "✓ Successfully added 'category' column!\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "✓ 'category' column already exists.\n";
}

// Show current table structure
echo "\nCurrent books table structure:\n";
echo "--------------------------------\n";
$result = $conn->query("DESCRIBE books");
while ($row = $result->fetch_assoc()) {
    echo sprintf("%-20s %-20s\n", $row['Field'], $row['Type']);
}

$conn->close();
echo "\n✓ Schema update completed.\n";
?>