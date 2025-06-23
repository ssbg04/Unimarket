<?php
$host = getenv("MYSQLHOST");         // mysql-wlgj.railway.internal
$user = getenv("MYSQLUSER");         // root
$password = getenv("MYSQLPASSWORD"); // your Railway password
$database = getenv("MYSQLDATABASE"); // railway
$port = getenv("MYSQLPORT");         // 3306

// Optional debug to confirm vars (remove in production)
// echo "HOST=$host<br>USER=$user<br>DB=$database<br>PORT=$port<br>";

// Check if environment variables are set
if (!$host || !$user || !$password || !$database || !$port) {
    die("Missing required database environment variables.");
}

// Create a connection
$conn = new mysqli($host, $user, $password, $database, (int)$port);

// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
    echo "Host: $host<br>User: $user<br>DB: $database<br>Port: $port<br>";

?>
