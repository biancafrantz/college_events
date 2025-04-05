<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['UID']) || $_SESSION['UserType'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

$adminID = $_SESSION['UID'];

// Get RSOs where this admin is a member
$rsos_query = $conn->prepare("
    SELECT RSO_ID, RSO_Name
    FROM RSOs
    WHERE Admin_ID = ?
");

$rsos_query->bind_param("i", $adminID);
$rsos_query->execute();
$rsos_result = $rsos_query->get_result();

$rsos = [];
while ($row = $rsos_result->fetch_assoc()) {
    $rsoID = $row['RSO_ID'];

    $members_query = $conn->prepare("
        SELECT U.Email
        FROM Users U
        JOIN RSO_Membership M ON U.UID = M.UID
        WHERE M.RSO_ID = ?
    ");
    $members_query->bind_param("i", $rsoID);
    $members_query->execute();
    $members_result = $members_query->get_result();

    $members = [];
    while ($m = $members_result->fetch_assoc()) {
        $members[] = $m['Email'];
    }

    $row['Members'] = $members;
    $rsos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script>
        function toggleSection(id) {
            const section = document.getElementById(id);
            section.style.display = section.style.display === 'block' ? 'none' : 'block';
        }
        function toggleDetail(id) {
            const detail = document.getElementById(id);
            detail.style.display = detail.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</head>
<body>
    <h1>Welcome to the Admin Dashboard</h1>
    <a href="../auth/logout.php"><button>Logout</button></a>

    <hr>

    <h2>Admin Actions</h2>
    <button class="toggle" onclick="toggleSection('create-event')">Create New Event</button>
    <button class="toggle" onclick="toggleSection('manage-events')">Manage My Events</button>
    <button class="toggle" onclick="toggleSection('manage-rsos')">Manage My RSOs</button>
    <button class="toggle" onclick="toggleSection('create-rso')">Create New RSO</button>
    <button class="toggle" onclick="toggleSection('view-all-events')">View All Events</button>

    <!-- Create Event Section -->
    <div id="create-event" class="section">
        <h3>Create New Event</h3>
        <form>
            <input type="text" placeholder="Event Name">
            <textarea placeholder="Event Description"></textarea>
            <input type="datetime-local">
            <select>
                <option value="">Select Event Type</option>
                <option value="Public">Public</option>
                <option value="Private">Private</option>
                <option value="RSO">RSO</option>
            </select>
            <input type="text" placeholder="Contact Email">
            <input type="text" placeholder="Contact Phone">

            <h4>Select Location</h4>
            <input type="text" placeholder="Location Name">
            <input type="text" placeholder="Address">
            <input type="text" placeholder="Latitude">
            <input type="text" placeholder="Longitude">
            <button>Create Event</button>
        </form>
    </div>

    <!-- Manage Events Section -->
    <div id="manage-events" class="section">
        <h3>Manage My Events</h3>
        <div class="card" onclick="toggleDetail('event1-detail')">
            <img src="https://via.placeholder.com/120x80?text=Career+Fair" class="card-image">
            <div>
                <h4>Career Fair</h4>
                <p>Public – May 10, 2025</p>
            </div>
        </div>
        <div id="event1-detail" class="detail">
            <p><strong>Description:</strong> Meet top employers from the region.</p>
            <button>Edit Event</button> <button>Delete Event</button>
        </div>
    </div>

    <!-- Manage RSOs Section -->
    <div id="manage-rsos" class="section">
        <h3>Manage My RSOs</h3>

        <?php foreach ($rsos as $rso): ?>
            <div class="card" onclick="toggleDetail('rso-detail-<?= $rso['RSO_ID'] ?>')">
                <img src="https://via.placeholder.com/120x80?text=<?= urlencode($rso['RSO_Name']) ?>" class="card-image">
                <div>
                    <h4><?= htmlspecialchars($rso['RSO_Name']) ?></h4>
                    <p>You are the admin</p>
                </div>
            </div>
            <div id="rso-detail-<?= $rso['RSO_ID'] ?>" class="detail">
                <h4>Members</h4>
                <ul>
                    <?php foreach ($rso['Members'] as $email): ?>
                        <li><?= htmlspecialchars($email) ?></li>
                    <?php endforeach; ?>
                </ul>

                <h4>Pending Join Requests</h4>
                <ul>
                    <li>student1@ucf.edu <button>Approve</button> <button>Deny</button></li>
                    <li>student2@ucf.edu <button>Approve</button> <button>Deny</button></li>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- View All Events Section -->
    <div id="view-all-events" class="section">
        <h3>All Events</h3>
        <div class="card" onclick="toggleDetail('event2-detail')">
            <img src="https://via.placeholder.com/120x80?text=Tech+Talk" class="card-image">
            <div>
                <h4>Tech Talk</h4>
                <p>RSO – April 25, 2025</p>
            </div>
        </div>
        <div id="event2-detail" class="detail">
            <p><strong>Description:</strong> AI in Industry</p>
            <textarea placeholder="Write a comment..."></textarea><br>
            <button>Submit</button> <button>Edit</button> <button>Delete</button>
            <h4>Rate this event</h4>
            <select>
                <option value="">Rate from 1 to 5</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
</body>
</html>