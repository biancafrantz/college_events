<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['UID']) || $_SESSION['UserType'] !== 'SuperAdmin') {
    header("Location: ../auth/login.php");
    exit();
}

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approveEvent']) || isset($_POST['rejectEvent'])) {
        $eventId = intval($_POST['eventId']);
        $newStatus = isset($_POST['approveEvent']) ? 'Approved' : 'Rejected';

        $stmt = $conn->prepare("UPDATE Public_Events SET Status = ?, SuperAdmin_ID = ? WHERE Event_ID = ?");
        $stmt->bind_param("sii", $newStatus, $_SESSION['UID'], $eventId);

        if ($stmt->execute()) {
            $successMessage = "Event has been " . strtolower($newStatus) . ".";
        } else {
            $errorMessage = "Failed to update event status.";
        }

        $stmt->close();

        // Optional: refresh page to update list
        header("Location: superAdmin_dash.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createUniversity'])) {
    $Name = trim($_POST['lname']);
    $Address = trim($_POST['address']);
    $Description = trim($_POST['Description']);
    $NumStudents = intval($_POST['NumStudents']);
    $Pictures = trim($_POST['Pictures']);
    $EmailDomain = trim($_POST['EmailDomain']);
    $Latitude = floatval($_POST['latitude']);
    $Longitude = floatval($_POST['longitude']);

    if ($Name && $Address && $Description && $_POST['NumStudents'] !== '' && $Pictures && $EmailDomain && $Latitude && $Longitude) {
        
        // Check for duplicate university
        $checkStmt = $conn->prepare("SELECT * FROM Universities WHERE Name = ?");
        $checkStmt->bind_param("s", $Name);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $errorMessage = "University already exists.";
        } else {
            // Insert into Location table first
            $locStmt = $conn->prepare("INSERT INTO Location (lname, address, latitude, longitude) VALUES (?, ?, ?, ?)");
            if ($locStmt) {
                $locStmt->bind_param("ssdd", $Name, $Address, $Latitude, $Longitude);
                if (!$locStmt->execute()) {
                    $errorMessage = "Location insert error: " . $locStmt->error;
                }
                $locStmt->close();
            } else {
                $errorMessage = "Location insert error: " . $conn->error;
            }

            // Only insert university if location insert succeeded
            if (empty($errorMessage)) {
                $stmt = $conn->prepare("INSERT INTO Universities (Name, Location, Description, NumStudents, Pictures, EmailDomain) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssiss", $Name, $Address, $Description, $NumStudents, $Pictures, $EmailDomain);
                    if ($stmt->execute()) {
                        $newUniversityID = $stmt->insert_id;
                        $successMessage = "University successfully created.";
                    
                        // Update users with matching email domains
                        $domainLike = '%' . '@' . $EmailDomain;
                        $updateStmt = $conn->prepare("UPDATE Users SET UniversityID = ? WHERE UniversityID IS NULL AND Email LIKE ?");
                        if ($updateStmt) {
                            $updateStmt->bind_param("is", $newUniversityID, $domainLike);
                            $updateStmt->execute();
                            $updateStmt->close();
                        }
                    } else {
                        $errorMessage = "University insert error: " . $stmt->error;
                    }                    
                    $stmt->close();
                } else {
                    $errorMessage = "University insert error: " . $conn->error;
                }
            }
        }

        $checkStmt->close();
    } else {
        $errorMessage = "All fields are required.";
    }
}

$universities = [];
$result = $conn->query("SELECT * FROM Universities");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $universities[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .section { display: none; margin-top: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; background: #fefefe; }
        .card {
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .card:hover { background-color: #f9f9f9; }
        .card-image {
            width: 120px;
            height: 80px;
            margin-right: 15px;
            object-fit: cover;
            border-radius: 4px;
        }
        .detail {
            display: none;
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            background: #fefefe;
        }
        .actions { margin-top: 10px; }
        input, select, textarea { width: 100%; padding: 5px; margin: 5px 0; }
        button.toggle { margin: 5px 10px 0 0; }

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
.logout-button:hover {
    background-color:rgb(160, 91, 86);
}

.button-group {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin: 20px auto 30px;
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

    </style>
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
<div class="dashboard-header">
<h1 style="text-align: center;">Welcome to the Super Admin Dashboard</h1>
    <a href="../auth/logout.php"><button class="logout-button">Logout</button></a>
    </div>

    <hr>

    <div class="button-group">
    <button class="toggle" onclick="toggleSection('createUniversity')">Create University</button>
    <button class="toggle" onclick="toggleSection('universityDirectory')">University Directory</button>
    <button class="toggle" onclick="toggleSection('approveEvents')">Approve Events</button>
</div>


    <!-- Create University Section -->
    <div id="createUniversity" class="section">
        <h3>Create University</h3>
        <form method="POST">

            <input type="text" name="lname" id="lname" placeholder="University Name" required>
            <div id="map" style="height: 300px; margin: 10px 0;"></div>
            <input type="text" name="latitude" id="latitude" placeholder="Latitude" readonly required>
            <input type="text" name="longitude" id="longitude" placeholder="Longitude" readonly required>
            <input type="text" name="address" id="address" placeholder="Address" readonly required>

            <textarea name="Description" placeholder="Description"></textarea>
            <input type="number" name="NumStudents" placeholder="Number of Students">
            <input type="text" name="Pictures" placeholder="Image URL">
            <input type="text" name="EmailDomain" placeholder="Email Domain (e.g., ucf.edu)" required>
            <button type="submit" name="createUniversity">Create University</button>
        </form>
        <?php if ($successMessage) echo "<p style='color:green;'>$successMessage</p>"; ?>
        <?php if ($errorMessage) echo "<p style='color:red;'>$errorMessage</p>"; ?>
    </div>

    <!-- University Directory Section -->
    <div id="universityDirectory" class="section">
        <h3>University Directory</h3>
        <?php foreach ($universities as $index => $uni): ?>
            <div class="card" onclick="toggleDetail('uni<?= $index ?>-detail')">
                <img src="<?= htmlspecialchars($uni['Pictures']) ?>" class="card-image">
                <div>
                    <h4><?= htmlspecialchars($uni['Name']) ?></h4>
                    <p><?= htmlspecialchars($uni['Location']) ?> – <?= number_format($uni['NumStudents']) ?> students</p>
                </div>
            </div>
            <div id="uni<?= $index ?>-detail" class="detail">
                <p><strong>Description:</strong> <?= htmlspecialchars($uni['Description']) ?></p>
                <p><strong>Email Domain:</strong> <?= htmlspecialchars($uni['EmailDomain']) ?></p>
                <p><strong>Images:</strong> <a href="<?= htmlspecialchars($uni['Pictures']) ?>" target="_blank">[Preview Link]</a></p>
                <button>Edit</button> <button>Delete</button>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Approve Events Section -->
    <div id="approveEvents" class="section">
        <h3>Approve Events</h3>

        <?php
        $pendingEvents = [];

        $sql = "
            SELECT e.Event_ID, e.Event_Name, e.Description, e.Event_Date, e.Start_Time, e.End_Time, l.address, l.lname, p.Status
            FROM Events e
            JOIN Public_Events p ON e.Event_ID = p.Event_ID
            JOIN Location l ON e.lname = l.lname
            WHERE p.Status = 'Pending'
            ORDER BY e.Event_Date ASC, e.Start_Time ASC
        ";

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pendingEvents[] = $row;
            }
        }

        if (empty($pendingEvents)) {
            echo "<p>No pending events to approve.</p>";
        }

        foreach ($pendingEvents as $index => $event):
        ?>
            <div class="card" onclick="toggleDetail('event<?= $index ?>-detail')">
                <div>
                    <h4><?= htmlspecialchars($event['Event_Name']) ?></h4>
                    <p>
                        <?= date("F j, Y", strtotime($event['Event_Date'])) ?>
                        @ <?= date("g:i A", strtotime($event['Start_Time'])) ?>
                        – <?= htmlspecialchars($event['lname']) ?>
                    </p>
                </div>
            </div>
            <div id="event<?= $index ?>-detail" class="detail">
                <p><strong>Description:</strong> <?= htmlspecialchars($event['Description']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($event['address']) ?></p>
                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="eventId" value="<?= $event['Event_ID'] ?>">
                    <button type="submit" name="approveEvent">Approve</button>
                    <button type="submit" name="rejectEvent">Reject</button>
                </form>
            </div>
        <?php endforeach; ?>
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

        document.getElementById("lname").addEventListener("change", function () {
            const name = this.value;
            if (name.trim() !== "") {
                geocodeUniversity(name);
            }
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

    function geocodeUniversity(name) {
        geocoder.geocode({ address: name }, (results, status) => {
            if (status === "OK" && results[0]) {
                const location = results[0].geometry.location;
                map.setCenter(location);
                marker.setPosition(location);
                updateFields({ lat: location.lat(), lng: location.lng() });
            } else {
                alert("Could not find that university name. Try searching manually in the map.");
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

    window.onload = initMap;
</script>

</body>
</html>