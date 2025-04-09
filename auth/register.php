<?php
include '../db_connect.php'; 

$message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['Name']) && !empty($_POST['Email']) && !empty($_POST['UserType']) && !empty($_POST['Password'])) {
        $Name = trim($_POST['Name']);
        $Email = trim($_POST['Email']);
        $UserType = $_POST['UserType'];
        $Password = password_hash($_POST['Password'], PASSWORD_DEFAULT);

        $emailParts = explode('@', $Email);
        $emailDomain = isset($emailParts[1]) ? strtolower($emailParts[1]) : null;

     
        $universityID = null;

        if ($emailDomain) {
        
            $uni_stmt = $conn->prepare("SELECT UniversityID FROM Universities WHERE LOWER(EmailDomain) = ?");
            $uni_stmt->bind_param("s", $emailDomain);
            $uni_stmt->execute();
            $uni_result = $uni_stmt->get_result();

            if ($uni_result->num_rows > 0) {
                $uni_row = $uni_result->fetch_assoc();
                $universityID = $uni_row['UniversityID'];
            }
        }

      
        $check_stmt = $conn->prepare("SELECT Email FROM Users WHERE Email = ?");
        $check_stmt->bind_param("s", $Email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "Error: This email is already registered!";
        } else {
         
            $stmt = $conn->prepare("INSERT INTO Users (Name, Email, UserType, Password, UniversityID) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $Name, $Email, $UserType, $Password, $universityID);

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
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
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
    <div class="centered-form">
        <h2>Register</h2>

        <!-- Display Messages -->
        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, 'Error') !== false ? 'error' : 'success' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <input type="text" name="Name" placeholder="Name" required>
            <input type="email" name="Email" placeholder="Email" required>
            <input type="password" name="Password" placeholder="Password" required>
            <select name="UserType" required>
                <option value="" disabled selected>Select User Type</option>
                <option value="Student">Student</option>
                <option value="Admin">Admin</option>
                <option value="SuperAdmin">Super Admin</option>
            </select>
            <input type="submit" value="Register">
        </form>

        <p>Already have an account?</p>
        <a href="login.php">
            <button type="button">Login</button>
        </a>
    </div>
</body>

</html>