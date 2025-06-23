<?php
// Load environment variables from Railway
$host = getenv("MYSQLHOST");
$user = getenv("MYSQLUSER");
$password = getenv("MYSQLPASSWORD");
$database = getenv("MYSQLDATABASE");
$port = getenv("MYSQLPORT");

// Fallback if any variable is missing (optional but helpful)
if (!$host || !$user || !$password || !$database || !$port) {
    die("Missing required database environment variables.");
}

// Create MySQL connection
$conn = new mysqli($host, $user, $password, $database, (int)$port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: success message
// echo "Connected to Railway MySQL successfully!";
?>
