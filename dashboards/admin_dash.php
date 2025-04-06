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

$rsoOptions = '';
foreach ($rsos as $rso) {
    $rsoOptions .= "<option value='{$rso['RSO_ID']}'>" . htmlspecialchars($rso['RSO_Name']) . "</option>";
}

$email_query = $conn->prepare("SELECT Email FROM Users WHERE UID = ?");
$email_query->bind_param("i", $adminID);
$email_query->execute();
$email_result = $email_query->get_result();
$email_row = $email_result->fetch_assoc();
$adminEmail = $email_row['Email'];

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
    background-color:rgb(195, 205, 233);
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
  <button class="toggle" onclick="toggleSection('create-rso')">Create New RSO</button>
  <button class="toggle" onclick="toggleSection('view-all-events')">View All Events</button>
</div>

<?php if (isset($_GET['event_status']) && $_GET['event_status'] === 'success'): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; border: 1px solid #c3e6cb; margin-bottom: 20px;">
        ✅ Event created successfully!
    </div>
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
    </div>
    </div>

    <!-- View All Events Section -->
    <div id="view-all-events" class="section">
    <div class="event-wrapper">
    <div class="section-glass">
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
            <button>Submit</button> 
            <button>Edit</button> 
            <button>Delete</button>
            
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
    </div>

    <script
    src="https://maps.googleapis.com/maps/api/js?key=<?php echo getenv('GOOGLE_MAPS_API'); ?>&libraries=places&callback=initMap"
        async defer></script> 

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
</script>
</body>
</html>