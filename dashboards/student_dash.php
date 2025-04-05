<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['UID']) || $_SESSION['UserType'] !== 'Student') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_rso'])) {
    $rsoName = trim($_POST['rso_name']);
    $description = trim($_POST['rso_description']);
    $memberEmails = array_filter([
        $_POST['email1'], $_POST['email2'], $_POST['email3'],
        $_POST['email4'], $_POST['email5']
    ]);

    // Must be 5 members
    if (count($memberEmails) !== 5) {
        $error = "You must provide 5 unique member emails.";
    } else {
        // Extract domains
        $domains = array_map(function($email) {
            return strtolower(substr(strrchr($email, "@"), 1));
        }, $memberEmails);

        if (count(array_unique($domains)) > 1) {
            $error = "All emails must be from the same university domain.";
        } else {
            $domain = $domains[0];

            // Look up university
            $univQuery = $conn->prepare("SELECT University FROM Users WHERE UID = ?");
            $univQuery->bind_param("i", $_SESSION['UID']);
            $univQuery->execute();
            $result = $univQuery->get_result();
            if ($row = $result->fetch_assoc()) {
                $university = $row['University'];
                // Ensure all users exist or insert them
                $uids = [];
                foreach ($memberEmails as $email) {
                    $stmt = $conn->prepare("SELECT UID FROM Users WHERE Email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->bind_result($uid);
                    if ($stmt->fetch()) {
                        $uids[] = $uid;
                    }if ($stmt->fetch()) {
                        $uids[] = $uid;
                    } else {
                        $error = "User with email $email does not exist in the system.";
                        break;
                    }
                    
                    $stmt->close();
                }

                // Insert RSO
                $adminUID = $_SESSION['UID'];
                $insertRSO = $conn->prepare("INSERT INTO RSOs (RSO_Name, Admin_ID) VALUES (?, ?)");
                $insertRSO->bind_param("si", $rsoName, $adminUID);
                if ($insertRSO->execute()) {
                    $rsoID = $insertRSO->insert_id;
                    foreach ($uids as $uid) {
                        $memInsert = $conn->prepare("INSERT INTO RSO_Membership (UID, RSO_ID) VALUES (?, ?)");
                        $memInsert->bind_param("ii", $uid, $rsoID);
                        $memInsert->execute();
                        $memInsert->close();
                    }
                    $success = "RSO '$rsoName' created successfully!";
                } else {
                    $error = "RSO creation failed: possibly a duplicate name.";
                }
            } else {
                $error = "No matching university for domain @$domain";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
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
    <h1>Welcome to the Student Dashboard</h1>
    <a href="../auth/logout.php"><button>Logout</button></a>

    <hr>

    <h2>Student Actions</h2>
    <button class="toggle" onclick="toggleSection('upcoming-events')">Upcoming Events</button>
    <button class="toggle" onclick="toggleSection('my-rsos')">My RSOs</button>
    <button class="toggle" onclick="toggleSection('create-rso')">Create New RSO</button>
    <button class="toggle" onclick="toggleSection('all-rsos')">All RSOs at My University</button>

    <!-- Upcoming Events -->
    <div id="upcoming-events" class="section" style="display: none;">
        <h3>Upcoming Events</h3>
        <div class="card" onclick="toggleDetail('event1-detail')">
            <img src="https://via.placeholder.com/120x80?text=Innovation" class="card-image">
            <div>
                <h4>Innovation Kickoff</h4>
                <p>RSO Event – April 20, 2025</p>
            </div>
        </div>
        <div id="event1-detail" class="detail">
            <p><strong>Date/Time:</strong> April 20, 2025 @ 4:00 PM</p>
            <p><strong>Location:</strong> Engineering Hall, Room 102</p>
            <p><strong>Contact:</strong> taylor.finch@ucf.edu, (407) 123-4567</p>
            <p><strong>Description:</strong> A student-led event to promote innovation and collaboration.</p>
            <div class="actions">
                <h4>Your Comment</h4>
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
    </div>

    <!-- My RSOs -->
    <div id="my-rsos" class="section" style="display: none;">
        <h3>Your RSOs</h3>
        <div class="card" onclick="toggleDetail('rso1-detail')">
            <img src="https://via.placeholder.com/120x80?text=Innovators" class="card-image">
            <div>
                <h4>Future Innovators</h4>
                <p>Member</p>
            </div>
        </div>
        <div id="rso1-detail" class="detail">
            <p><strong>Focus:</strong> Promoting innovation and technology.</p>
            <p><strong>Admin:</strong> taylor.finch@ucf.edu</p>
            <button>Leave RSO</button>
        </div>
    </div>

    <!-- Create RSO -->
    <div id="create-rso" class="section" style="display: none;">
    <h3>Create New RSO</h3>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <form method="POST">
        <input type="text" name="rso_name" placeholder="RSO Name" required>
        <textarea name="rso_description" placeholder="RSO Description" required></textarea>
        <input type="email" name="email1" placeholder="Member Email 1" required>
        <input type="email" name="email2" placeholder="Member Email 2" required>
        <input type="email" name="email3" placeholder="Member Email 3" required>
        <input type="email" name="email4" placeholder="Member Email 4" required>
        <input type="email" name="email5" placeholder="Member Email 5" required>
        <button type="submit" name="create_rso">Create RSO</button>
    </form>
</div>


    <!-- All RSOs -->
    <div id="all-rsos" class="section" style="display: none;">
        <h3>All RSOs at Your University</h3>
        <div class="card" onclick="toggleDetail('rso2-detail')">
            <img src="https://via.placeholder.com/120x80?text=Knight+Hacks" class="card-image">
            <div>
                <h4>Knight Hacks</h4>
                <p>UCF’s official hackathon team</p>
            </div>
        </div>
        <div id="rso2-detail" class="detail">
            <p><strong>Mission:</strong> Building coding skills and competitive programming through hackathons.</p>
            <p><strong>Contact:</strong> knight@ucf.edu</p>
            <button>Request to Join</button>
        </div>

        <div class="card" onclick="toggleDetail('rso3-detail')">
            <img src="https://via.placeholder.com/120x80?text=NSBE" class="card-image">
            <div>
                <h4>NSBE</h4>
                <p>National Society of Black Engineers</p>
            </div>
        </div>
        <div id="rso3-detail" class="detail">
            <p><strong>Mission:</strong> To increase the number of culturally responsible Black engineers who excel academically and positively impact the community.</p>
            <p><strong>Contact:</strong> nsbe@ucf.edu</p>
            <button>Request to Join</button>
        </div>
    </div>
</body>
</html>