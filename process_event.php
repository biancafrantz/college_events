<?php
require 'db_connect.php'; // DB connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Grab form inputs
        $ename = $_POST["ename"];
        $edesc = $_POST["edesc"];
        $eventDate = $_POST["Event_Date"];
        $startTime = $_POST["Start_Time"];
        $endTime = $_POST["End_Time"];
        $eventType = $_POST["eventType"];
        $contactEmail = $_POST["contact_email"];
        $phone = $_POST["phone"];
        $lname = $_POST["lname"];
        $address = $_POST["address"];
        $roomNumber = trim($_POST["room_number"] ?? '');
        if (!empty($roomNumber)) {
            $address .= " Room $roomNumber";
        }
        $latitude = $_POST["latitude"];
        $longitude = $_POST["longitude"];
        $rsoID = $_POST['rso'] ?? null;

        // Get current user's UID from session (assuming it's stored there)
        session_start();
        $adminID = $_SESSION['UID'] ?? null;

        if (!$adminID) {
            echo "<script>
                alert('Error: Unauthorized. Please log in again.');
                window.location.href = 'login.php';
            </script>";
            exit;
        }

        // Insert or check location
        $locCheck = $conn->prepare("SELECT lname FROM Location WHERE lname = ?");
        $locCheck->bind_param("s", $lname);
        $locCheck->execute();
        $locCheck->store_result();

        if ($locCheck->num_rows == 0) {
            $insertLoc = $conn->prepare("INSERT INTO Location (lname, address, latitude, longitude) VALUES (?, ?, ?, ?)");
            $insertLoc->bind_param("ssdd", $lname, $address, $latitude, $longitude);
            $insertLoc->execute();
        }

        // Insert into Events table (now includes Contact_Email and Contact_Phone)
        $eventStmt = $conn->prepare("
            INSERT INTO Events (Event_Name, Description, Event_Date, Start_Time, End_Time, lname, Contact_Email, Contact_Phone)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $eventStmt->bind_param("ssssssss", $ename, $edesc, $eventDate, $startTime, $endTime, $lname, $contactEmail, $phone);
        $eventStmt->execute();

        $eventID = $conn->insert_id;

        // Insert into correct event type table
        if ($eventType === "public") {
            $status = 'Pending';
            $superAdminID = null;
            $stmt = $conn->prepare("INSERT INTO Public_Events (Event_ID, Admin_ID, SuperAdmin_ID, Status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $eventID, $adminID, $superAdminID, $status);
        } elseif ($eventType === "private") {
            $stmt = $conn->prepare("INSERT INTO Private_Events (Event_ID, Admin_ID) VALUES (?, ?)");
            $stmt->bind_param("ii", $eventID, $adminID);
        } elseif ($eventType === "rso" && $rsoID) {
            $stmt = $conn->prepare("INSERT INTO RSO_Events (Event_ID, RSO_ID) VALUES (?, ?)");
            $stmt->bind_param("ii", $eventID, $rsoID);
        }

        if (isset($stmt) && $stmt->execute()) {
            header("Location: dashboards/admin_dash.php?event_status=success");
        } else {
            header("Location: dashboards/admin_dash.php?event_status=error");
        }
        exit();

    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            // Duplicate entry error
            header("Location: dashboards/admin_dash.php?event_status=duplicate");
            exit();
        } else {
            // Other MySQL error
            error_log("Database error: " . $e->getMessage());
            header("Location: dashboards/admin_dash.php?event_status=error");
            exit();
        }
    }
}
