<?php
include '../db_connect.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['Name'], $_POST['Email'], $_POST['UserType'], $_POST['Password'])) {
        $Name = $_POST['Name'];
        $Email = $_POST['Email'];
        $UserType = $_POST['UserType'];
        $Password = password_hash($_POST['Password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO Users (Name, Email, UserType, Password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $Name, $Email, $UserType, $Password);

        if ($stmt->execute()) {
            header("Location: ../frontend/register.html?success=1");
            exit();

        } else {
            header("Location: ../frontend/register.html?error=1");
            exit();

        }
    } else {
        echo "Missing form fields!";
    }
}
?>
