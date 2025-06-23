<?php
$host = getenv("MYSQLHOST");
$user = getenv("MYSQLUSER");
$password = getenv("MYSQLPASSWORD");
$db = getenv("MYSQLDATABASE");
$port = getenv("MYSQLPORT");

$conn = new mysqli($host, $user, $password, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
