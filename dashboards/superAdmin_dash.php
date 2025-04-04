


<?php
session_start();
if (!isset($_SESSION['UID']) || $_SESSION['UserType'] !== 'SuperAdmin') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
   
    <script>
        function toggleSection(id) {
            const section = document.getElementById(id);
            if (section) {
                section.style.display = (section.style.display === 'block' ? 'none' : 'block');
            }
        }

        function toggleDetail(id) {
            const detail = document.getElementById(id);
            if (detail) {
                detail.style.display = (detail.style.display === 'block' ? 'none' : 'block');
            }
        }
    </script>
</head>
<body>
    <h1>Welcome to the Super Admin Dashboard</h1>
    <a href="../auth/logout.php"><button>Logout</button></a>

    <hr>

    <h2>Super Admin Actions</h2>
    <button class="toggle" onclick="toggleSection('create-university')">Create University</button>
    <button class="toggle" onclick="toggleSection('university-directory')">University Directory</button>
    <button class="toggle" onclick="toggleSection('approve-events')">Approve Events</button>

    <!-- Create University Section -->
    <div id="create-university" class="section">
        <h3>Create University</h3>
        <form>
            <input type="text" placeholder="University Name">
            <input type="text" placeholder="Location">
            <textarea placeholder="Description"></textarea>
            <input type="number" placeholder="Number of Students">
            <input type="text" placeholder="Image URL">
            <input type="text" placeholder="Email Domain (e.g., ucf.edu)">
            <button>Create University</button>
        </form>
    </div>

    <!-- University Directory Section -->
    <div id="university-directory" class="section">
        <h3>University Directory</h3>
        <div class="card" onclick="toggleDetail('uni1-detail')">
            <img src="https://via.placeholder.com/120x80?text=UCF" class="card-image">
            <div>
                <h4>University of Central Florida</h4>
                <p>Orlando, FL – 70,000 students</p>
            </div>
        </div>
        <div id="uni1-detail" class="detail">
            <p><strong>Description:</strong> Large public university focused on innovation.</p>
            <p><strong>Email Domain:</strong> ucf.edu</p>
            <p><strong>Images:</strong> <a href="#">[Preview Link]</a></p>
            <button>Edit</button> <button>Delete</button>
        </div>

        <div class="card" onclick="toggleDetail('uni2-detail')">
            <img src="https://via.placeholder.com/120x80?text=UF" class="card-image">
            <div>
                <h4>University of Florida</h4>
                <p>Gainesville, FL – 55,000 students</p>
            </div>
        </div>
        <div id="uni2-detail" class="detail">
            <p><strong>Description:</strong> Flagship university with strong research programs.</p>
            <p><strong>Email Domain:</strong> ufl.edu</p>
            <p><strong>Images:</strong> <a href="#">[Preview Link]</a></p>
            <button>Edit</button> <button>Delete</button>
        </div>
    </div>

    <!-- Approve Events Section (Corrected) -->
    <div id="approve-events" class="section">
        <h3>Approve Events</h3>

        <button class="toggle" onclick="toggleSection('private-events-sub')">Toggle Private Events</button>
        <div id="private-events-sub" style="display: none; margin-top: 10px;">
            <h4>Pending Private Events</h4>
            <div class="card" onclick="toggleDetail('private-event-careerprep')">
                <img src="https://via.placeholder.com/120x80?text=Career+Prep" class="card-image" alt="Career Prep">
                <div>
                    <h4>Career Prep Workshop</h4>
                    <p>Private – Pending</p>
                </div>
            </div>
            <div id="private-event-careerprep" class="detail">
                <p><strong>Date/Time:</strong> April 21, 2025 @ 6:00 PM</p>
                <p><strong>University:</strong> University of Central Florida</p>
                <p><strong>Location:</strong> Business Building, Room 201</p>
                <p><strong>Contact:</strong> admin@ucf.edu, (407) 987-6543</p>
                <p><strong>Description:</strong> Tips and strategies to prepare for your job search and interviews.</p>
                <button>Approve</button> <button>Reject</button>
            </div>
        </div>

        <button class="toggle" onclick="toggleSection('public-events-sub')">Toggle Public Events</button>
        <div id="public-events-sub" style="display: none; margin-top: 10px;">
            <h4>Pending Public Events (Require Super Admin Final Approval)</h4>
            <div class="card" onclick="toggleDetail('public-event-cleanup')">
                <img src="https://via.placeholder.com/120x80?text=Cleanup+Day" class="card-image" alt="Campus Cleanup">
                <div>
                    <h4>Campus Cleanup Day</h4>
                    <p>Public – Awaiting Super Admin</p>
                </div>
            </div>
            <div id="public-event-cleanup" class="detail">
                <p><strong>Date/Time:</strong> April 22, 2025 @ 10:00 AM</p>
                <p><strong>Location:</strong> UCF Reflecting Pond</p>
                <p><strong>Contact:</strong> outreach@ucf.edu, (407) 555-1212</p>
                <p><strong>Description:</strong> Volunteer effort to clean the campus and promote sustainability.</p>
                <button>Review & Submit to Super Admin</button>
            </div>
        </div>
    </div>
</body>
</html>
