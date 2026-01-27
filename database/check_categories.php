<?php
require_once dirname(__DIR__) . '/config/db.php';

echo "Checking categories in books table...\n\n";

// Get all distinct categories
$result = $conn->query("SELECT DISTINCT category FROM books ORDER BY category");

echo "Current categories in database:\n";
echo "--------------------------------\n";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cat = $row['category'] ? $row['category'] : '(NULL/Empty)';
        echo "- " . $cat . "\n";
    }
} else {
    echo "No categories found.\n";
}

// Count books per category
echo "\nBooks per category:\n";
echo "--------------------------------\n";
$result = $conn->query("SELECT category, COUNT(*) as count FROM books GROUP BY category ORDER BY category");
while ($row = $result->fetch_assoc()) {
    $cat = $row['category'] ? $row['category'] : '(NULL/Empty)';
    echo $cat . ": " . $row['count'] . " books\n";
}

// Update NULL/empty categories to 'General'
echo "\nUpdating empty categories to 'General'...\n";
$conn->query("UPDATE books SET category = 'General' WHERE category IS NULL OR category = ''");
echo "✓ Done!\n";

$conn->close();
?>