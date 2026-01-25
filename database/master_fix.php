<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once 'config/db.php';

echo "<h1>Master Database Fixer</h1>";
echo "<p>Checking database structure...</p>";

// 1. Check 'users' table for 'favorite_genres'
echo "<h3>Checking 'users' table...</h3>";
$check_genres = $conn->query("SHOW COLUMNS FROM users LIKE 'favorite_genres'");
if ($check_genres->num_rows == 0) {
    echo "Adding 'favorite_genres' column... ";
    if ($conn->query("ALTER TABLE users ADD favorite_genres TEXT")) {
        echo "<span style='color:green'>Done.</span><br>";
    } else {
        echo "<span style='color:red'>Failed: " . $conn->error . "</span><br>";
    }
} else {
    echo "<span style='color:green'>'favorite_genres' exists.</span><br>";
}

// 2. Check 'users' table for 'profile_picture'
$check_pic = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
if ($check_pic->num_rows == 0) {
    echo "Adding 'profile_picture' column... ";
    if ($conn->query("ALTER TABLE users ADD profile_picture VARCHAR(255) DEFAULT NULL")) {
        echo "<span style='color:green'>Done.</span><br>";
    } else {
        echo "<span style='color:red'>Failed: " . $conn->error . "</span><br>";
    }
} else {
    echo "<span style='color:green'>'profile_picture' exists.</span><br>";
}

// 3. Check 'books' table for 'seller_id'
echo "<h3>Checking 'books' table...</h3>";
$check_seller = $conn->query("SHOW COLUMNS FROM books LIKE 'seller_id'");
if ($check_seller->num_rows == 0) {
    echo "Adding 'seller_id' column... ";
    if ($conn->query("ALTER TABLE books ADD seller_id INT DEFAULT NULL")) {
        echo "<span style='color:green'>Done.</span><br>";
    } else {
        echo "<span style='color:red'>Failed: " . $conn->error . "</span><br>";
    }
} else {
    echo "<span style='color:green'>'seller_id' exists.</span><br>";
}

echo "<hr>";
echo "<h2>Fix Complete!</h2>";
echo "<p>Your database is now fully updated and ready.</p>";
echo "<a href='preferences.php' style='padding:10px 20px; background:blue; color:white; text-decoration:none; border-radius:5px;'>Return to Setup</a>";
?>