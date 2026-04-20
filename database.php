<?php
$host = "localhost";     // Change if your DB is hosted elsewhere
$user = "root";          // MySQL username
$pass = "";              // MySQL password
$dbname = "medi";    // Database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Optional: set UTF-8 encoding
$conn->set_charset("utf8");
?>
