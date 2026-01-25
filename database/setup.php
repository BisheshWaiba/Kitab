<?php
$host = 'localhost';
$user = 'root';
$pass = '';

// Connect without database
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create Database
$sql = "CREATE DATABASE IF NOT EXISTS kitab_db";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select Database
$conn->select_db("kitab_db");

// Read and execute database.sql
$sql_file = file_get_contents('database.sql');
$queries = explode(';', $sql_file);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if ($conn->query($query) === TRUE) {
            // Success
        } else {
            echo "Error executing query: " . $conn->error . "<br>";
        }
    }
}

echo "<h3>Setup Complete!</h3>";
echo "<p>Database and tables created successfully.</p>";
echo "<a href='../frontend/index.php'>Go to Homepage</a>";

$conn->close();
?>