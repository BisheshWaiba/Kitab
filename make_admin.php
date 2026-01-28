<?php
require_once 'config/db.php';

echo "=== Checking All Users in Database ===\n\n";

// First, show all existing users
$result = $conn->query("SELECT id, name, email, role FROM users");

if ($result && $result->num_rows > 0) {
    echo "Current Users:\n";
    echo "==============\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Email: " . $row['email'] . " | Role: " . $row['role'] . "\n";
    }
    echo "\n";
} else {
    echo "No users found in database.\n\n";
}

// Now create or update Bishesh Waiba as admin
$name = "Bishesh Waiba";
$email = "bishesh@admin.com";
$password = password_hash("admin123", PASSWORD_DEFAULT); // Default password: admin123
$role = "admin";

// Check if user already exists
$check = $conn->query("SELECT id FROM users WHERE email = '$email'");

if ($check->num_rows > 0) {
    // User exists, update to admin
    $sql = "UPDATE users SET role = 'admin', name = '$name' WHERE email = '$email'";
    if ($conn->query($sql) === TRUE) {
        echo "✅ User updated to admin!\n";
    }
} else {
    // Create new admin user
    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
    if ($conn->query($sql) === TRUE) {
        echo "✅ SUCCESS! Admin account created!\n\n";
        echo "Admin Login Credentials:\n";
        echo "========================\n";
        echo "Email: $email\n";
        echo "Password: admin123\n";
        echo "Role: admin\n\n";
        echo "⚠️ IMPORTANT: Please change the password after first login!\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
}

$conn->close();
?>