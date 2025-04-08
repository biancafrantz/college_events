<?php
$servername = "104.248.123.245"; // Change to secret??
$username = "wamp_user1"; // Change to secret??
$password = "TermProject1!"; // Change to secret??
$dbname = "COP4710"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>