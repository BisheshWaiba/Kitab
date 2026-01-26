<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password
$db_name = 'kitab_db';

define('BASE_URL', 'http://localhost:8000/');

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>