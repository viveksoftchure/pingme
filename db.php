<?php
// Database connection
$host = 'localhost';        // phpMyAdmin host
$db = 'pingme';       // Database name
$user = 'root';             // Default username
$pass = '';                 // Default password (leave empty unless changed)

// Connect to MySQL
$conn = new mysqli($host, $user, $pass, $db);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
