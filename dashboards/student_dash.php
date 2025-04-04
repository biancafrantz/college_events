

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
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    
    <script>
        function toggle(id) {
            const elem = document.getElementById(id);
            elem.style.display = (elem.style.display === 'none' || elem.style.display === '') ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <h1>Welcome to the Student Dashboard</h1>
    <a href="../auth/logout.php"><button>Logout</button></a>

    <hr>

    <h2>Upcoming Events</h2>

    <!-- Event Card -->
    <div class="card" onclick="toggle('event1-details')">
        <img src="https://via.placeholder.com/120x80?text=Innovation" alt="Innovation Kickoff" class="card-image">
        <div>
            <h3>Innovation Kickoff</h3>
            <p>RSO Event – April 20, 2025</p>
        </div>
    </div>
    <div id="event1-details" class="detail">
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

    <!-- More event cards... -->

    <hr>

    <h2>Your RSOs</h2>

    <div class="card" onclick="toggle('rso1-details')">
        <img src="https://via.placeholder.com/120x80?text=Innovators" alt="Future Innovators" class="card-image">
        <div>
            <h3>Future Innovators</h3>
            <p>Member</p>
        </div>
    </div>
    <div id="rso1-details" class="detail">
        <p><strong>Focus:</strong> Promoting innovation and technology.</p>
        <p><strong>Admin:</strong> taylor.finch@ucf.edu</p>
        <button>Leave RSO</button>
    </div>

    <!-- More joined RSOs... -->

    <hr>

    <h2>All RSOs at Your University</h2>

    <div class="card" onclick="toggle('rso2-details')">
        <img src="https://via.placeholder.com/120x80?text=Knight+Hacks" alt="Knight Hacks" class="card-image">
        <div>
            <h3>Knight Hacks</h3>
            <p>UCF’s official hackathon team</p>
        </div>
    </div>
    <div id="rso2-details" class="detail">
        <p><strong>Mission:</strong> Building coding skills and competitive programming through hackathons.</p>
        <p><strong>Contact:</strong> knight@ucf.edu</p>
        <button>Request to Join</button>
    </div>

    <div class="card" onclick="toggle('rso3-details')">
        <img src="https://via.placeholder.com/120x80?text=NSBE" alt="NSBE" class="card-image">
        <div>
            <h3>NSBE</h3>
            <p>National Society of Black Engineers</p>
        </div>
    </div>
    <div id="rso3-details" class="detail">
        <p><strong>Mission:</strong> To increase the number of culturally responsible Black engineers who excel academically and positively impact the community.</p>
        <p><strong>Contact:</strong> nsbe@ucf.edu</p>
        <button>Request to Join</button>
    </div>

    <a href="request_new_rso.php"><button>Request to Create New RSO</button></a>
</body>
</html>
