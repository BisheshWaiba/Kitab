<?php
require_once '../config/db.php';

// Try to add the column
$sql = "ALTER TABLE users ADD COLUMN favorite_genres TEXT AFTER password";

if ($conn->query($sql) === TRUE) {
    echo "<h3>Success!</h3>";
    echo "<p>Database table 'users' updated. Added column 'favorite_genres'.</p>";
    echo "<p><a href='preferences.php'>Go back to Genre Selection</a></p>";
} else {
    echo "<h3>Status</h3>";
    echo "<p>Message: " . $conn->error . "</p>";
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "<p>The column already exists. You are good to go!</p>";
        echo "<p><a href='preferences.php'>Go back to Genre Selection</a></p>";
    }
}
?>