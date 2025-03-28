<?php
$servername = "104.248.123.245"; 
$username = "wamp_user1"; 
$password = "TermProject1!"; 
$dbname = "COP4710"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
