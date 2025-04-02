<?php
session_start();
if (!isset($_SESSION['UID']) || $_SESSION['UserType'] !== 'Student') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../db_connect.php'; // Ensure database connection is included

$uid = $_SESSION['UID'];
$sql = "SELECT University FROM Users WHERE UID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();

$university = "Unknown University"; // Default if no university is found
if ($row = $result->fetch_assoc()) {
    $university = $row['University'];
}

$stmt->close();
$conn->close();
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
    <p>Your University: <?php echo htmlspecialchars($university); ?></p> <!-- Display university -->
    <a href="../auth/logout.php"><button>Logout</button></a>
</body>
</html>
