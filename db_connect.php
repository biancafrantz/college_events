<?php
$servername = "104.248.123.245"; 
$username = "brandon"; 
$password = "Password123!"; 
$dbname = "COP4710"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>