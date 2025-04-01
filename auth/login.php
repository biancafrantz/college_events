<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/COP4710_termProject/db_connect.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Email = $_POST['Email'];
    $PasswordInput = $_POST['Password'];

    $stmt = $conn->prepare("SELECT UID, Password, UserType FROM Users WHERE Email = ?");
    $stmt->bind_param("s", $Email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {  // Move fetch inside this block
        $stmt->bind_result($UID, $HashedPassword, $UserType);
        $stmt->fetch();

        if (password_verify($PasswordInput, $HashedPassword)) {
            $_SESSION['UID'] = $UID;
            $_SESSION['UserType'] = $UserType;

            // Debug output to confirm before redirect
            echo "Redirecting UserType: $UserType...<br>";

            // Redirect based on UserType
            if ($UserType === 'SuperAdmin') {
                header("Location: ../dashboards/superAdmin_dash.php");
            } elseif ($UserType === 'Admin') {
                header("Location: ../dashboards/admin_dash.php");
            } else {
                header("Location: ../dashboards/student_dash.php");
            }
            exit();
        } else {
            echo "Invalid password!";
        }
    } else {
        echo "Invalid email!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <form action="login.php" method="POST">
        Email: <input type="email" name="Email" required><br>
        Password: <input type="password" name="Password" required><br>
        <input type="submit" value="Login">
    </form>

    <p>Don't have an account? 
        <a href="register.php">
            <button type="button">Register</button>
        </a>
    </p>
</body>
</html>

