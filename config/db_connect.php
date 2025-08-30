<?php
// Set default timezone to Asia/Kolkata to align with MySQL's apparent timezone
date_default_timezone_set('Asia/Kolkata');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "e_learning_platform";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>