<?php
include '../db_connect.php'; // Include database connection

$message = ""; // To store success or error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['Name']) && !empty($_POST['Email']) && !empty($_POST['UserType']) && !empty($_POST['Password'])) {
        $Name = trim($_POST['Name']);
        $Email = trim($_POST['Email']);
        $UserType = $_POST['UserType'];
        $Password = password_hash($_POST['Password'], PASSWORD_DEFAULT);

        // Check if email is already in use
        $check_stmt = $conn->prepare("SELECT Email FROM Users WHERE Email = ?");
        $check_stmt->bind_param("s", $Email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "Error: This email is already registered!";
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO Users (Name, Email, UserType, Password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $Name, $Email, $UserType, $Password);

            if ($stmt->execute()) {
                $message = "Registration successful! You can now <a href='login.php'>login</a>.";
            } else {
                $message = "Error: Registration failed. Please try again.";
            }
        }
    } else {
        $message = "Error: All fields are required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        .message {
            padding: 10px;
            margin: 10px 0;
            font-weight: bold;
            border-radius: 5px;
        }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h2>Register</h2>

    <!-- Display Messages -->
    <?php if (!empty($message)): ?>
        <div class="message <?= strpos($message, 'Error') !== false ? 'error' : 'success' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        Name: <input type="text" name="Name" required><br>
        Email: <input type="email" name="Email" required><br>
        UserType:
        <select name="UserType">
            <option value="Student">Student</option>
            <option value="Admin">Admin</option>
            <option value="SuperAdmin">Super Admin</option>
        </select>
        <br>
        Password: <input type="password" name="Password" required><br>
        <input type="submit" value="Register">
    </form>

    <p>Already have an account? 
        <a href="login.php"><button type="button">Login</button></a>
    </p>
</body>
</html>
