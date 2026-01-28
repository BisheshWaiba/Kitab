<?php
require_once 'config/db.php';

if ($conn->ping()) {
    echo "Database connection successful!\n";
    echo "Connected to database: $db_name\n";

    // Check if books table exists
    $result = $conn->query("SHOW TABLES LIKE 'books'");
    if ($result->num_rows > 0) {
        echo "Table 'books' exists.\n";
        $count = $conn->query("SELECT count(*) as c FROM books")->fetch_assoc()['c'];
        echo "Books count: $count\n";
    } else {
        echo "Table 'books' does NOT exist.\n";
    }

} else {
    echo "Database connection failed: " . $conn->error . "\n";
}
?>