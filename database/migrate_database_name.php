<?php
/**
 * Database Migration Script
 * Renames database from 'kitab_db' to '25123794'
 * 
 * Run this script ONCE to migrate your existing database
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$old_db = 'kitab_db';
$new_db = '25123794';

// Connect to MySQL server (not to a specific database)
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to MySQL server successfully.\n\n";

// Check if old database exists
$result = $conn->query("SHOW DATABASES LIKE '$old_db'");
$old_exists = $result->num_rows > 0;

// Check if new database exists
$result = $conn->query("SHOW DATABASES LIKE '$new_db'");
$new_exists = $result->num_rows > 0;

if ($new_exists) {
    echo "✓ Database '$new_db' already exists.\n";
    echo "  No migration needed.\n";
} elseif ($old_exists) {
    echo "→ Renaming database from '$old_db' to '$new_db'...\n";

    // Create new database
    if ($conn->query("CREATE DATABASE `$new_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        echo "  ✓ Created new database '$new_db'\n";

        // Get all tables from old database
        $conn->select_db($old_db);
        $tables = $conn->query("SHOW TABLES");

        if ($tables->num_rows > 0) {
            echo "  → Copying tables...\n";
            while ($row = $tables->fetch_array()) {
                $table = $row[0];

                // Copy table structure and data
                $conn->query("CREATE TABLE `$new_db`.`$table` LIKE `$old_db`.`$table`");
                $conn->query("INSERT INTO `$new_db`.`$table` SELECT * FROM `$old_db`.`$table`");

                echo "    ✓ Copied table: $table\n";
            }

            echo "\n  ✓ All tables copied successfully!\n";
            echo "\n→ Old database '$old_db' is still available.\n";
            echo "  You can drop it manually after verifying the migration:\n";
            echo "  DROP DATABASE `$old_db`;\n";
        } else {
            echo "  ! No tables found in old database.\n";
        }
    } else {
        echo "  ✗ Error creating new database: " . $conn->error . "\n";
    }
} else {
    echo "! Neither '$old_db' nor '$new_db' exists.\n";
    echo "  Creating new database '$new_db'...\n";

    if ($conn->query("CREATE DATABASE `$new_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        echo "  ✓ Created database '$new_db'\n";
        echo "\n  ! You need to import your database schema.\n";
        echo "    Run the SQL files in the /database folder.\n";
    } else {
        echo "  ✗ Error: " . $conn->error . "\n";
    }
}

$conn->close();
echo "\n✓ Migration script completed.\n";
?>