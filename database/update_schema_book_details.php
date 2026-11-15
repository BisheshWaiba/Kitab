<?php
// Adds the optional book details (condition, language, format, year published) to the books table.
// Safe to run more than once. The Sell a Book page also does this by itself when a column is missing.
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';
require_once '../includes/helpers.php';

echo "<h1>Database Update: Book Details</h1>";

k_book_columns($conn);

foreach (['book_condition', 'language', 'format', 'published_year'] as $column) {
    $found = $conn->query("SHOW COLUMNS FROM books LIKE '$column'");
    if ($found && $found->num_rows > 0) {
        echo "<p style='color:green'>Column '$column' is in the 'books' table.</p>";
    } else {
        echo "<p style='color:red'>Could not add the column '$column': " . htmlspecialchars($conn->error) . "</p>";
    }
}

echo "<hr>";
echo "<a href='../frontend/sell_book.php'>Go to Sell a Book</a>";
?>
