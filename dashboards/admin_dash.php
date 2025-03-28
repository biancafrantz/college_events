<?php
session_start();
if (!isset($_SESSION['UID']) || $_SESSION['UserType'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>Welcome to the Admin Dashboard</h1>
    <p>Hello, Admin! You have access to administrative features.</p>
    <a href="../auth/logout.php"><button>Logout</button></a>
</body>
</html>
