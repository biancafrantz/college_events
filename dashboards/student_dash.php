<?php
session_start();
if (!isset($_SESSION['UID']) || $_SESSION['UserType'] !== 'Student') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
</head>
<body>
    <h1>Welcome to the Student Dashboard</h1>
    <p>Hello, Student! You are logged in.</p>
    <a href="../auth/logout.php"><button>Logout</button></a>
</body>
</html>
