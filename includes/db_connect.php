<?php
$host = "localhost:3306";
$username = "root";
$password = "12345678";
$database = "fanimation";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    // Log error for debugging (don't expose in production)
    error_log("Connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please try again later.");
}

// Set charset to prevent encoding issues
mysqli_set_charset($conn, "utf8mb4");
