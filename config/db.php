<?php
// config/db.php
$host = "localhost";
$username = "root";
$password = "";  // EMPTY password for XAMPP
$database = "complaint_management_system";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");
?>