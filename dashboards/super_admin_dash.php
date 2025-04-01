<?php
session_start();
if (!isset($_SESSION['uid']) || $_SESSION['UserType'] !== 'SuperAdmin') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
</head>
<body>
    <h1>Welcome to the Super Admin Dashboard</h1>
    <p>Hello, Super Admin! You have the highest level of access.</p>
    <a href="../auth/logout.php"><button>Logout</button></a>
</body>
</html>
