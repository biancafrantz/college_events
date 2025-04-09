<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['UID']) || $_SESSION['UserType'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

$adminID = $_SESSION['UID'];

function updateRSOStatus($conn, $rsoID) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM RSO_Membership WHERE RSO_ID = ?");
    $stmt->bind_param("i", $rsoID);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $status = $count >= 5 ? 'Active' : 'Inactive';
    $update = $conn->prepare("UPDATE RSOs SET Status = ? WHERE RSO_ID = ?");
    $update->bind_param("si", $status, $rsoID);
    $update->execute();
    $update->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $eventID = intval($_POST['event_id']);
    $name = trim($_POST['event_name']);
    $desc = trim($_POST['event_desc']);
    $date = $_POST['event_date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    $stmt = $conn->prepare("UPDATE Events SET Event_Name = ?, Description = ?, Event_Date = ?, Start_Time = ?, End_Time = ? WHERE Event_ID = ?");
    $stmt->bind_param("sssssi", $name, $desc, $date, $start, $end, $eventID);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dash.php?event_updated=1");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_comment'])) {
        $eventID = $_POST['event_id'];
        $text = trim($_POST['comment_text']);
        $rating = intval($_POST['rating']);
        if ($text !== '' && $rating >= 1 && $rating <= 5) {
            $stmt = $conn->prepare("INSERT INTO Comments (Event_ID, UID, Text, Rating) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $eventID, $_SESSION['UID'], $text, $rating);
            $stmt->execute();
            $stmt->close();
        }
    }

    if (isset($_POST['edit_comment']) && isset($_POST['comment_text'])) {
        $commentID = $_POST['comment_id'];
        $text = trim($_POST['comment_text']);
        $rating = intval($_POST['rating']);
        $stmt = $conn->prepare("UPDATE Comments SET Text = ?, Rating = ? WHERE Comment_ID = ? AND UID = ?");
        $stmt->bind_param("siii", $text, $rating, $commentID, $_SESSION['UID']);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['delete_comment'])) {
        $commentID = $_POST['comment_id'];
        if ($_SESSION['UserType'] === 'Admin') {
            $stmt = $conn->prepare("DELETE FROM Comments WHERE Comment_ID = ?");
            $stmt->bind_param("i", $commentID);
        } else {
            $stmt = $conn->prepare("DELETE FROM Comments WHERE Comment_ID = ? AND UID = ?");
            $stmt->bind_param("ii", $commentID, $_SESSION['UID']);
        }
        
        $stmt->execute();
        $stmt->close();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rso'])) {
    $rsoID = intval($_POST['rso_id']);

    // Delete all associated events first
    $conn->query("DELETE e FROM Events e JOIN RSO_Events re ON e.Event_ID = re.Event_ID WHERE re.RSO_ID = $rsoID");

    // Delete from RSO_Events (in case events still exist somehow)
    $conn->query("DELETE FROM RSO_Events WHERE RSO_ID = $rsoID");

    // Delete RSO memberships
    $conn->query("DELETE FROM RSO_Membership WHERE RSO_ID = $rsoID");

    // Finally, delete the RSO itself
    $conn->query("DELETE FROM RSOs WHERE RSO_ID = $rsoID");

    header("Location: admin_dash.php?rso_deleted=1");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_member'])) {
    $rsoID = intval($_POST['rso_id']);
    $uid = intval($_POST['uid']);

    $stmt = $conn->prepare("DELETE FROM RSO_Membership WHERE UID = ? AND RSO_ID = ?");
    $stmt->bind_param("ii", $uid, $rsoID);
    $stmt->execute();
    $stmt->close();

    updateRSOStatus($conn, $rsoID);
    header("Location: admin_dash.php?member_removed=1");
    exit();
}


if (isset($_POST['add_member'])) {
    $email = trim($_POST['new_member_email']);
    $rsoID = intval($_POST['rso_id']);

    $stmt = $conn->prepare("SELECT UID, UserType, UniversityID FROM Users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($uid, $type, $univ);
    if ($stmt->fetch() && $type === 'Student') {
        $stmt->close();

        $already = $conn->prepare("SELECT 1 FROM RSO_Membership WHERE UID = ? AND RSO_ID = ?");
        $already->bind_param("ii", $uid, $rsoID);
        $already->execute();
        $already->store_result();

        if ($already->num_rows === 0) {
            $add = $conn->prepare("INSERT INTO RSO_Membership (UID, RSO_ID) VALUES (?, ?)");
            $add->bind_param("ii", $uid, $rsoID);
            $add->execute();
            $add->close();
            updateRSOStatus($conn, $rsoID);
        }
        $already->close();
    } else {
        $stmt->close();
    }
}

if (isset($_POST['delete_member'])) {
    $uid = intval($_POST['uid']);
    $rsoID = intval($_POST['rso_id']);
    $del = $conn->prepare("DELETE FROM RSO_Membership WHERE UID = ? AND RSO_ID = ?");
    $del->bind_param("ii", $uid, $rsoID);
    $del->execute();
    $del->close();
    updateRSOStatus($conn, $rsoID);
}





// Get RSOs where this admin is a member
$rsos_query = $conn->prepare("
    SELECT RSO_ID, RSO_Name, Description, Status
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
SELECT U.UID, U.Email
        FROM Users U
        JOIN RSO_Membership M ON U.UID = M.UID
        WHERE M.RSO_ID = ?
    ");
    $members_query->bind_param("i", $rsoID);
    $members_query->execute();
    $members_result = $members_query->get_result();

    $members = [];
    while ($m = $members_result->fetch_assoc()) {
        $members[$m['UID']] = $m['Email'];
    }
    

    $row['Members'] = $members;
    $rsos[] = $row;
}

$allRsoStmt = $conn->prepare("SELECT RSO_ID, RSO_Name, Admin_ID FROM RSOs");
$allRsoStmt->execute();
$allRsos = $allRsoStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$allRsoStmt->close();

$rsoOptions = '';
foreach ($allRsos as $rso) {
    $label = htmlspecialchars($rso['RSO_Name']);
    $selected = isset($_POST['rso']) && $_POST['rso'] == $rso['RSO_ID'] ? 'selected' : '';
    $rsoOptions .= "<option value='{$rso['RSO_ID']}' $selected data-admin='{$rso['Admin_ID']}'>$label</option>";
}


$email_query = $conn->prepare("SELECT Email FROM Users WHERE UID = ?");
$email_query->bind_param("i", $adminID);
$email_query->execute();
$email_result = $email_query->get_result();
$email_row = $email_result->fetch_assoc();
$adminEmail = $email_row['Email'];

// Get Public Events created by this admin
$publicEventsStmt = $conn->prepare("
    SELECT e.*, l.address, 'Public' AS Type
FROM Events e
JOIN Location l ON e.lname = l.lname
    JOIN Public_Events pe ON e.Event_ID = pe.Event_ID
    WHERE pe.Admin_ID = ?
");
$publicEventsStmt->bind_param("i", $adminID);
$publicEventsStmt->execute();
$publicResult = $publicEventsStmt->get_result();
$publicEvents = $publicResult->fetch_all(MYSQLI_ASSOC);
$publicEventsStmt->close();

// Get Private Events created by this admin
$privateEventsStmt = $conn->prepare("
    SELECT e.*, l.address, 'Public' AS Type
FROM Events e
JOIN Location l ON e.lname = l.lname
    JOIN Private_Events pr ON e.Event_ID = pr.Event_ID
    WHERE pr.Admin_ID = ?
");
$privateEventsStmt->bind_param("i", $adminID);
$privateEventsStmt->execute();
$privateResult = $privateEventsStmt->get_result();
$privateEvents = $privateResult->fetch_all(MYSQLI_ASSOC);
$privateEventsStmt->close();

// Get RSO Events managed by this admin
$rsoEventsStmt = $conn->prepare("
    SELECT e.*, l.address, 'Public' AS Type
FROM Events e
JOIN Location l ON e.lname = l.lname
    JOIN RSO_Events re ON e.Event_ID = re.Event_ID
    JOIN RSOs r ON re.RSO_ID = r.RSO_ID
    WHERE r.Admin_ID = ?
");
$rsoEventsStmt->bind_param("i", $adminID);
$rsoEventsStmt->execute();
$rsoResult = $rsoEventsStmt->get_result();
$rsoEvents = $rsoResult->fetch_all(MYSQLI_ASSOC);
$rsoEventsStmt->close();

// Merge all into one array
$myEvents = array_merge($publicEvents, $privateEvents, $rsoEvents);
$myCreatedEvents = $myEvents;

// Get the admin's university ID
$universityID = null;
$univStmt = $conn->prepare("SELECT UniversityID FROM Users WHERE UID = ?");
$univStmt->bind_param("i", $adminID);
$univStmt->execute();
$univStmt->bind_result($universityID);
$univStmt->fetch();
$univStmt->close();

// Public events at the same university
$publicEvents = [];
$publicStmt = $conn->prepare("
    SELECT e.*, l.address, 'Public' AS Type
    FROM Events e
    JOIN Public_Events p ON e.Event_ID = p.Event_ID
    JOIN Users u ON p.Admin_ID = u.UID
    JOIN Location l ON e.Lname = l.lname
    WHERE u.UniversityID = ? AND e.Event_Date >= CURDATE()
");
$publicStmt->bind_param("i", $universityID);
$publicStmt->execute();
$publicEvents = $publicStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$publicStmt->close();

// Private events from the same university
$privateEvents = [];
$privateStmt = $conn->prepare("
    SELECT e.*, l.address, 'Private' AS Type
    FROM Events e
    JOIN Private_Events p ON e.Event_ID = p.Event_ID
    JOIN Users u ON p.Admin_ID = u.UID
    JOIN Location l ON e.Lname = l.lname
    WHERE u.UniversityID = ? AND e.Event_Date >= CURDATE()
");
$privateStmt->bind_param("i", $universityID);
$privateStmt->execute();
$privateEvents = $privateStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$privateStmt->close();

// RSO events where admin is a member
$rsoEvents = [];
$rsoStmt = $conn->prepare("
    SELECT e.*, l.address, 'RSO' AS Type
    FROM Events e
    JOIN RSO_Events re ON e.Event_ID = re.Event_ID
    JOIN RSO_Membership rm ON re.RSO_ID = rm.RSO_ID
    JOIN Location l ON e.Lname = l.lname
    WHERE rm.UID = ? AND e.Event_Date >= CURDATE()
");
$rsoStmt->bind_param("i", $adminID);
$rsoStmt->execute();
$rsoEvents = $rsoStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rsoStmt->close();

// Merge into $upcomingEvents
$upcomingEvents = array_merge($publicEvents, $privateEvents, $rsoEvents);
usort($upcomingEvents, function ($a, $b) {
    return strtotime($a['Event_Date'] . ' ' . $a['Start_Time']) <=> strtotime($b['Event_Date'] . ' ' . $b['Start_Time']);
});

usort($upcomingEvents, function ($a, $b) {
    return strtotime($a['Event_Date'] . ' ' . $a['Start_Time']) <=> strtotime($b['Event_Date'] . ' ' . $b['Start_Time']);
});




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

        function showSection(id) {
    const section = document.getElementById(id);
    const currentlyVisible = section.style.display === 'block';

    document.querySelectorAll('.section').forEach(div => div.style.display = 'none');

    if (!currentlyVisible) {
        section.style.display = 'block';
        localStorage.setItem('activeTab', id);
        localStorage.setItem('tabOpen', 'true');
    } else {
        section.style.display = 'none';
        localStorage.setItem('activeTab', id);
        localStorage.setItem('tabOpen', 'false');
    }
}

window.onload = function () {
    const tab = localStorage.getItem('activeTab');
    const tabOpen = localStorage.getItem('tabOpen');

    if (tab && tabOpen === 'true') {
        const section = document.getElementById(tab);
        if (section) section.style.display = 'block';
    }
};

    </script>
</head>

<style>
 body {
  background: url('background.jpg') no-repeat center center fixed;
  background-size: cover;
  background-color: transparent;
  font-family: 'Inter', sans-serif;
}

h1, h2, h3, h4 {
    font-weight: 600;
}

.section {
  display: none;
  padding: 40px 0;
  background: transparent;
}

button, input, select, textarea {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    margin-bottom: 10px;
    box-sizing: border-box;
}

button {
    background-color:#c3cde9;
    color: black;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background-color:rgb(225, 226, 230);
}

.toggle {
    margin-right: 10px;
    margin-bottom: 10px;
}

.card {
    display: flex;
    align-items: center;
    background: #f9f9f9;
    border-radius: 8px;
    padding: 12px;
    margin: 10px 0;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.card-image {
    width: 120px;
    height: 80px;
    border-radius: 8px;
    object-fit: cover;
    margin-right: 12px;
}

.detail {
    background: #fff;
    padding: 12px;
    border-left: 4px solid #4CAF50;
    margin-bottom: 20px;
    border-radius: 6px;
    display: none;
}

textarea,
input[type="text"],
input[type="email"],
input[type="datetime-local"],
select {
    vertical-align: middle;
    box-sizing: border-box;
    padding: 10px;
    border-radius: 6px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
}

textarea {
    height: 40px; /* Match the height of adjacent inputs */
    resize: vertical;
}

.event-wrapper {
  display: flex;
  justify-content: center;
  margin-top: 40px;
}

.section-glass {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(6px);
  padding: 30px;
  border-radius: 12px;
  width:100%;
  max-width: 1200px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
}


.logout-button:hover {
    background-color:rgb(160, 91, 86);
}

.dashboard-header {
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  padding: 5px;
}

.dashboard-title {
  text-align: center;
  flex: 1;
  font-size: 38px;
  font-weight: 600;
}

.logout-button {
  position: absolute;
  right: 20px;
  top: 20px;
  background-color:rgb(195, 205, 233);
  border: none;
  padding: 10px 16px;
  border-radius: 6px;
  color: black;
  font-weight: bold;
  cursor: pointer;
}

.admin-button-group {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
  margin: 20px auto;
}

    </style>
<body>
    
<div class="dashboard-header">
  <h1 class="dashboard-title">Welcome to the Admin Dashboard</h1>
  <a href="../auth/logout.php"><button class="logout-button">Logout</button></a>
</div>

    <hr>

    <div class="admin-button-group">
  <button class="toggle" onclick="toggleSection('create-event')">Create New Event</button>
  <button class="toggle" onclick="toggleSection('manage-events')">Manage My Events</button>
  <button class="toggle" onclick="toggleSection('manage-rsos')">Manage My RSOs</button>
  <button class="toggle" onclick="toggleSection('upcoming-events')">Upcoming Events</button>
  <button class="toggle" onclick="toggleSection('view-all-events')">View All My Events</button>
 


</div>

<?php if (isset($_GET['event_status']) && $_GET['event_status'] === 'success'): ?>
  <div id="event-created-msg" style="background-color: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; border: 1px solid #c3e6cb; margin-bottom: 20px; text-align: center;">
    ✅ Event created successfully!
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const msg = document.getElementById('event-created-msg');
      if (msg) {
        setTimeout(() => {
          msg.style.display = 'none';
          const url = new URL(window.location);
          url.searchParams.delete('event_status');
          window.history.replaceState({}, document.title, url.toString());
        }, 4000);
      }
    });
  </script>
<?php endif; ?>


<?php if (isset($_GET['event_deleted']) && $_GET['event_deleted'] == 1): ?>
  <div id="event-deleted-msg" style="background-color: #cde9da; color: #c3cde9; padding: 12px 20px; border-radius: 8px; border: 1px solid #f5c6cb; margin-bottom: 20px; text-align: center;">
    🗑️ Event deleted successfully.
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const msg = document.getElementById('event-deleted-msg');
      if (msg) {
        setTimeout(() => {
          msg.style.display = 'none';
          const url = new URL(window.location);
          url.searchParams.delete('event_deleted');
          window.history.replaceState({}, document.title, url.toString());
        }, 4000);
      }
    });
  </script>
<?php endif; ?>

<?php if (isset($_GET['rso_deleted']) && $_GET['rso_deleted'] == 1): ?>
  <div id="rso-deleted-msg" style="background-color: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px; border: 1px solid #f5c6cb; margin-bottom: 20px; text-align: center;">
    🗑️ RSO deleted successfully.
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const msg = document.getElementById('rso-deleted-msg');
      if (msg) {
        setTimeout(() => {
          msg.style.display = 'none';
          const url = new URL(window.location);
          url.searchParams.delete('rso_deleted');
          window.history.replaceState({}, document.title, url.toString());
        }, 4000);
      }
    });
  </script>
<?php endif; ?>


    <!-- Create Event Section -->
    <div id="create-event" class="section">
    <div class="event-wrapper">
    <div class="section-glass">
      <h3>Create New Event</h3>
      <form method="POST" action="../process_event.php">
        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            <input type="text" name="ename" placeholder="Event Name">
            <textarea name="edesc" placeholder="Event Description" style="height: 40px;"></textarea>
            <input type="date" name="Event_Date" required>
            <input type="time" name="Start_Time" required>
            <input type="time" name="End_Time" required>

            <label style="display: flex; align-items: center; gap: 8px;">

            Event Type:
            <select id="eventType" name="eventType" required onchange="toggleRSOField()">
              <option value="public">Public</option>
              <option value="private">Private</option>
              <option value="rso">RSO</option>
            </select>
          </label>

          <label id="rsoSelector" style="display: none; align-items: center; gap: 8px;">
            Select RSO:
            <select id="rso" name="rso">
              <?php echo $rsoOptions; ?>
            </select>
          </label>
        </div>

        <input type="email" name="contact_email" value="<?= htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8'); ?>" readonly>
        <input type="text" placeholder="Contact Phone" name="phone">

        <h4>Select Location</h4>
        <input type="text" name="lname" id="lname" placeholder="Search Location Name" required>

        <div id="map" style="width: 100%; height: 300px; border-radius: 10px; margin-bottom: 10px;"></div>

        <input id="address" type="text" placeholder="Address" name="address">
        <label style="display: flex; align-items: center; gap: 8px;">
  <input type="checkbox" id="roomToggle" onchange="toggleRoomInput()"> Include Room Number
</label>
<input id="room_number" type="text" name="room_number" placeholder="Room Number" style="display:none;">

        <input id="latitude" type="text" placeholder="Latitude" name="latitude">
        <input id="longitude" type="text" placeholder="Longitude" name="longitude">

        <button type="submit">Create Event</button>
      </form>
    </div>
  </div>
</div>

<!-- Manage Events Section -->
<div id="manage-events" class="section">
<div class="event-wrapper">
<div class="section-glass">
    <h3>Manage My Events</h3>

    <?php if (empty($myCreatedEvents)): ?>
        <p>You haven’t created any events yet.</p>
    <?php endif; ?>

    <?php foreach ($myCreatedEvents as $index => $event): ?>
        <div class="card" onclick="toggleDetail('manage-event<?= $index ?>')">
            <img src="https://via.placeholder.com/120x80?text=<?= urlencode($event['Event_Name']) ?>" class="card-image">
            <div>
                <h4><?= htmlspecialchars($event['Event_Name']) ?></h4>
                <p><?= htmlspecialchars($event['Type']) ?> – <?= date("F j, Y", strtotime($event['Event_Date'])) ?></p>
            </div>
        </div>

        <div id="manage-event<?= $index ?>" class="detail">
            <form method="POST">
                <input type="hidden" name="event_id" value="<?= $event['Event_ID'] ?>">
                <label>Name: <input type="text" name="event_name" value="<?= htmlspecialchars($event['Event_Name']) ?>"></label><br>
                <label>Description: <textarea name="event_desc"><?= htmlspecialchars($event['Description']) ?></textarea></label><br>
                <label>Date: <input type="date" name="event_date" value="<?= $event['Event_Date'] ?>"></label><br>
                <label>Start Time: <input type="time" name="start_time" value="<?= $event['Start_Time'] ?>"></label><br>
                <label>End Time: <input type="time" name="end_time" value="<?= $event['End_Time'] ?>"></label><br>
                <button type="submit" name="update_event">Save Changes</button>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this event?');">
    <input type="hidden" name="event_id" value="<?= $event['Event_ID'] ?>">
    <button type="submit" name="delete_event" style="background-color: #c0392b; color: white;">Delete Event</button>
</form>

            </form>
        </div>
    <?php endforeach; ?>
</div>
</div>
</div>


    <!-- Manage RSOs Section -->
    <div id="manage-rsos" class="section">
  <div class="event-wrapper">
    <div class="section-glass">
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
          <p><strong>Description:</strong> <?= htmlspecialchars($rso['Description'] ?? '') ?>
          </p>
          <p><strong>Status:</strong> <?= htmlspecialchars($rso['Status']) ?></p>
          <h4>Members</h4>
          <ul>
  <?php foreach ($rso['Members'] as $uid => $email): ?>
    <li>
      <?= htmlspecialchars($email) ?>
      <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this member?')">
        <input type="hidden" name="rso_id" value="<?= $rso['RSO_ID'] ?>">
        <input type="hidden" name="uid" value="<?= $uid ?>">
        <button type="submit" name="delete_member" style="background-color:#c0392b; color:white;">Delete</button>
      </form>
    </li>
  <?php endforeach; ?>
</ul>

<form method="POST">
  <input type="hidden" name="rso_id" value="<?= $rso['RSO_ID'] ?>">
  <input type="email" name="new_member_email" placeholder="Add member email" <?= count($rso['Members']) >= 5 ? 'disabled' : '' ?>>
  <button type="submit" name="add_member" <?= count($rso['Members']) >= 5 ? 'disabled' : '' ?>>Add Member</button>
  <?php if ($rso['Status'] === 'Active'): ?>
    <span style="color:rgb(0, 0, 0); margin-left: 10px;">There are already 5 members</span>
  <?php endif; ?>
</form>

          </ul>
          <form method="POST" onsubmit="return confirm('Are you sure you want to delete this RSO and all its data?');">
    <input type="hidden" name="rso_id" value="<?= $rso['RSO_ID'] ?>">
    <button type="submit" name="delete_rso" style="background-color:#c0392b; color:white;">Delete RSO</button>
</form>

        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>


<!-- Upcoming Events -->
<div id="upcoming-events" class="section" style="display:none; padding-top:20px;">
  <div class="event-wrapper">
    <div class="section-glass">
      <h3>Upcoming Events</h3>
      <?php if (empty($upcomingEvents)): ?>
        <p>No upcoming events found.</p>
      <?php endif; ?>

      <?php foreach ($upcomingEvents as $index => $event): ?>
        <?php
          $eventID = $event['Event_ID'];
          $stmt = $conn->prepare("
              SELECT c.Comment_ID, c.Text, c.Rating, c.UID, u.Name
              FROM Comments c
              JOIN Users u ON c.UID = u.UID
              WHERE c.Event_ID = ?
              ORDER BY c.Timestamp DESC
          ");
          $stmt->bind_param("i", $eventID);
          $stmt->execute();
          $res = $stmt->get_result();
          $comments = [];
          while ($row = $res->fetch_assoc()) $comments[] = $row;
          $stmt->close();
        ?>

        <div class="event-card">
          <div style="display: flex; gap: 40px;">
            <div style="flex: 1;">
              <h3><?= htmlspecialchars($event['Event_Name']) ?> (<?= $event['Type'] ?>)</h3>
              <p><?= htmlspecialchars($event['Description']) ?></p>
              <p><strong>Date:</strong> <?= htmlspecialchars($event['Event_Date']) ?> | 
                 <strong>Time:</strong> <?= htmlspecialchars($event['Start_Time']) ?> - <?= htmlspecialchars($event['End_Time']) ?></p>
              <p><strong>Location:</strong> <?= htmlspecialchars($event['address']) ?></p>

              <form method="POST" onsubmit="localStorage.setItem('activeTab', 'upcoming-events'); localStorage.setItem('tabOpen' , 'true')">
                <input type="hidden" name="event_id" value="<?= $eventID ?>">
                <textarea name="comment_text" placeholder="Write a comment..." required></textarea><br>
                <select name="rating" required>
                  <option value="">Rate</option>
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                  <?php endfor; ?>
                </select><br>
                <button name="submit_comment">Submit</button>
              </form>
            </div>

            <div style="flex: 1;">
              <h4>Comments</h4>
              <?php foreach ($comments as $c): ?>
                <div style="margin-bottom: 15px; border: 1px solid #ccc; padding: 10px; border-radius: 6px;">
                  <strong><?= htmlspecialchars($c['Name']) ?></strong><br>
                  <?= str_repeat('⭐', $c['Rating']) . str_repeat('☆', 5 - $c['Rating']) ?><br>

                  <?php if ($c['UID'] == $_SESSION['UID'] && isset($_POST['edit_comment']) && $_POST['comment_id'] == $c['Comment_ID'] && !isset($_POST['comment_text'])): ?>
                    <form method="POST">
                      <input type="hidden" name="comment_id" value="<?= $c['Comment_ID'] ?>">
                      <textarea name="comment_text"><?= htmlspecialchars($c['Text']) ?></textarea><br>
                      <select name="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                          <option value="<?= $i ?>" <?= $i == $c['Rating'] ? 'selected' : '' ?>><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                      </select><br>
                      <button name="edit_comment">Save</button>
                    </form>
                  <?php else: ?>
                    <p><?= nl2br(htmlspecialchars($c['Text'])) ?></p>
                    <?php if ($c['UID'] == $_SESSION['UID']): ?>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="comment_id" value="<?= $c['Comment_ID'] ?>">
                        <button name="edit_comment">Edit</button>
                      </form>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="comment_id" value="<?= $c['Comment_ID'] ?>">
                        <button name="delete_comment">Delete</button>
                      </form>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- View All Events -->
<div id="view-all-events" class="section" style="display:none; padding-top:20px;">
  <div class="event-wrapper">
    <div class="section-glass">
      <h3>All Events</h3>

      <?php if (empty($myEvents)): ?>
        <p>No events found.</p>
      <?php endif; ?>

      <?php foreach ($myEvents as $index => $event): ?>
        <div class="card" onclick="toggleDetail('event<?= $index ?>-detail')">
          <img src="https://via.placeholder.com/120x80?text=<?= urlencode($event['Event_Name']) ?>" class="card-image">
          <div>
            <h4><?= htmlspecialchars($event['Event_Name']) ?></h4>
            <p><?= htmlspecialchars($event['Type']) ?> – <?= date("F j, Y", strtotime($event['Event_Date'])) ?></p>
          </div>
        </div>
        <div id="event<?= $index ?>-detail" class="detail">
          <p><strong>Description:</strong> <?= htmlspecialchars($event['Description']) ?></p>
          <p><strong>Starts:</strong> <?= date("g:i A", strtotime($event['Start_Time'])) ?></p>
          <p><strong>Ends:</strong> <?= date("g:i A", strtotime($event['End_Time'])) ?></p>
          <p><strong>Location:</strong> <?= htmlspecialchars($event['address']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>




   
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD0Mkr4rl5p0wuJBe7LvHlLcX_duqvwX88&libraries=places&callback=initMap" async defer></script>
<script>
    let map, marker, geocoder, autocomplete;

    function initMap() {
        const defaultLocation = { lat: 28.6024, lng: -81.2001 }; // UCF coords
        geocoder = new google.maps.Geocoder();

        map = new google.maps.Map(document.getElementById("map"), {
            center: defaultLocation,
            zoom: 14,
        });

        marker = new google.maps.Marker({
            position: defaultLocation,
            map: map,
            draggable: true,
        });

        updateFields(defaultLocation);

        marker.addListener('dragend', function (event) {
            const position = {
                lat: event.latLng.lat(),
                lng: event.latLng.lng(),
            };
            updateFields(position);
        });

        const searchBox = new google.maps.places.SearchBox(document.getElementById("lname"));
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(document.getElementById("lname"));

        searchBox.addListener("places_changed", () => {
            const places = searchBox.getPlaces();
            if (places.length === 0) return;

            const place = places[0];
            if (!place.geometry || !place.geometry.location) return;

            const position = {
                lat: place.geometry.location.lat(),
                lng: place.geometry.location.lng()
            };

            map.setCenter(position);
            marker.setPosition(position);
            updateFields(position);
        });
    }

    function geocodePlace(name) {
        geocoder.geocode({ address: name }, (results, status) => {
            if (status === "OK" && results[0]) {
                const location = results[0].geometry.location;
                map.setCenter(location);
                marker.setPosition(location);
                updateFields({ lat: location.lat(), lng: location.lng() });
            } else {
                alert("Could not find that location. Try searching manually on the map.");
            }
        });
    }

    function updateFields(position) {
        document.getElementById("latitude").value = position.lat;
        document.getElementById("longitude").value = position.lng;

        geocoder.geocode({ location: position }, (results, status) => {
            if (status === "OK" && results[0]) {
                document.getElementById("address").value = results[0].formatted_address;
            }
        });
    }

    function toggleRSOField() {
        const eventType = document.getElementById("eventType").value;
        const rsoSelector = document.getElementById("rsoSelector");
        if (eventType === "rso") {
            rsoSelector.style.display = "block";
        } else {
            rsoSelector.style.display = "none";
        }
    }

    function toggleRoomInput() {
  const checkbox = document.getElementById("roomToggle");
  const input = document.getElementById("room_number");
  input.style.display = checkbox.checked ? "block" : "none";
}

</script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const msg = document.getElementById('event-deleted-msg');
    if (msg) {
      setTimeout(() => {
        msg.style.display = 'none';
        const url = new URL(window.location);
        url.searchParams.delete('event_deleted');
        window.history.replaceState({}, document.title, url.toString());
      }, 4000);
    }
  });
</script>
<script>
document.querySelector('form[action="../process_event.php"]').addEventListener('submit', function(e) {
  const type = document.getElementById('eventType').value;
  if (type === 'rso') {
    const select = document.getElementById('rso');
    const selectedOption = select.options[select.selectedIndex];
    const selectedAdmin = selectedOption.getAttribute('data-admin');
    const currentAdmin = <?= json_encode($adminID) ?>;
    if (parseInt(selectedAdmin) !== currentAdmin) {
      e.preventDefault();
      alert("You are not the admin of this RSO.");
    }
  }
});
</script>

</body>
</html>