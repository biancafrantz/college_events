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
    $memberEmails = array_unique(array_filter([
        $_POST['email1'], $_POST['email2'], $_POST['email3'], $_POST['email4']
    ]));

    if (count($memberEmails) !== 4) {
        $error = "You must provide 4 unique additional member emails.";
    } else {
        $uids = [];
        $universityID = null;

        foreach ($memberEmails as $email) {
            $stmt = $conn->prepare("SELECT UID, UserType, UniversityID FROM Users WHERE Email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($uid, $userType, $userUnivID);

            if ($stmt->fetch()) {
                if ($userType !== 'Student') {
                    $error = "User with email $email is not a Student.";
                    break;
                }

                if ($universityID === null) {
                    $universityID = $userUnivID;
                } elseif ($universityID !== $userUnivID) {
                    $error = "All users must belong to the same university.";
                    break;
                }

                $uids[] = $uid;
            } else {
                $error = "User with email $email does not exist.";
                break;
            }

            $stmt->close();
        }

        if (!isset($error)) {
            $adminUID = $_SESSION['UID'];

            // Create the RSO
            $insertRSO = $conn->prepare("INSERT INTO RSOs (RSO_Name, Admin_ID) VALUES (?, ?)");
            $insertRSO->bind_param("si", $rsoName, $adminUID);

            if ($insertRSO->execute()) {
                $rsoID = $insertRSO->insert_id;

                // Add members to RSO_Membership
                foreach ($uids as $uid) {
                    $memInsert = $conn->prepare("INSERT INTO RSO_Membership (UID, RSO_ID) VALUES (?, ?)");
                    $memInsert->bind_param("ii", $uid, $rsoID);
                    $memInsert->execute();
                    $memInsert->close();
                }

                // Promote creator to Admin if not already
                $promoteStmt = $conn->prepare("UPDATE Users SET UserType = 'Admin' WHERE UID = ? AND UserType = 'Student'");
                $promoteStmt->bind_param("i", $adminUID);
                $promoteStmt->execute();
                $promoteStmt->close();

                $success = "RSO '$rsoName' created successfully!";
            } else {
                $error = "RSO creation failed: possibly a duplicate name.";
            }

            $insertRSO->close();
        }
    }
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
        $stmt = $conn->prepare("DELETE FROM Comments WHERE Comment_ID = ? AND UID = ?");
        $stmt->bind_param("ii", $commentID, $_SESSION['UID']);
        $stmt->execute();
        $stmt->close();
    }
}

$eventID = 1;
$comments = [];
$stmt = $conn->prepare("
    SELECT c.Comment_ID, c.Text, c.Rating, c.UID, u.Name
    FROM Comments c
    JOIN Users u ON c.UID = u.UID
    WHERE c.Event_ID = ?
    ORDER BY c.Timestamp DESC
");
$stmt->bind_param("i", $eventID);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $comments[] = $row;
$stmt->close();

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
    <div class="dashboard-header">
    <h1>Welcome to the Student Dashboard</h1>
    <a href="../auth/logout.php"><button class="logout-button">Logout</button></a>
    </div>
    <hr>

    <div class="student-button-group">
        <button onclick="showSection('upcoming-events')">Upcoming Events</button>
        <button onclick="showSection('create-rso')">Create New RSO</button>
    </div>

    <!-- Upcoming Events -->
    <div id="upcoming-events" class="section" style="display:none; padding-top:20px;">
    <div class="event-wrapper">
    <div class="section-glass">
      <div style="display: flex; gap: 40px;">
        <div style="flex: 1;">
          <h3>Upcoming Event: Innovation Kickoff</h3>
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
            <?php
            $filled = str_repeat('⭐', $c['Rating']);
            $empty = str_repeat('☆', 5 - $c['Rating']);
            echo "Stars: $filled$empty";
            ?>

              <?php if ($c['UID'] == $_SESSION['UID'] && isset($_POST['edit_comment']) && $_POST['comment_id'] == $c['Comment_ID'] && !isset($_POST['comment_text'])): ?>
                <form method="POST" onsubmit="localStorage.setItem('activeTab', 'upcoming-events'); localStorage.setItem('tabOpen', 'true')">
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
                  <form method="POST" style="display:inline;" onsubmit="localStorage.setItem('activeTab', 'upcoming-events'); localStorage.setItem('tabOpen' , 'true')">
                    <input type="hidden" name="comment_id" value="<?= $c['Comment_ID'] ?>">
                    <button name="edit_comment">Edit</button>
                  </form>
                  <form method="POST" style="display:inline;" onsubmit="localStorage.setItem('activeTab', 'upcoming-events'); localStorage.setItem('tabOpen' , 'true')">
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
  </div>
</div>


<style>
body {
    background: url('your-background.jpg') no-repeat center center fixed;
  background-size: cover;
  background-color: transparent;
}

.section {
  margin-top: 30px;
  display: none;
  background: transparent !important;
}

.rso-wrapper {
    display: flex;
    justify-content: center;
    padding-top: 40px;
}

.rso-form-container {
    max-width: 600px;
    width: 80%;
}

.rso-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.rso-row {
    display: flex;
    gap: 12px;
}

.rso-row input[type="text"], .rso-row textarea {
    flex: 1;
}

.rso-form input[type="email"], .rso-form textarea, .rso-form input[type="text"] {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.rso-form textarea {
    resize: vertical;
    min-height: 80px;
}

.rso-form button {
    background-color:rgb(195, 205, 233);
    color: black;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
}

.rso-form button:hover {
    background-color:rgb(225, 226, 230);
}

.dynamic-email {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.email-wrapper {
    margin-top: 8px;
}

.email-wrapper input {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.section-glass {
  background: rgba(255, 255, 255, 0.55);
  backdrop-filter: blur(6px);
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  width: 100%;
  max-width: 1000px;
}


.dashboard-header {
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  padding: 20px;
}

.dashboard-title {
  flex: 1;
  text-align: center;
  font-size: 28px;
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
.logout-button:hover {
    background-color:rgb(160, 91, 86);
}

.student-button-group {
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

.event-wrapper {
  display: flex;
  justify-content: center;
  padding-top: 40px;
  padding-bottom: 40px;
}

</style>

    <!-- Create RSO -->
    <div id="create-rso" class="section" style="display: none;">
    <div class="event-wrapper">
    <div class="section-glass">
    <div class="rso-wrapper">
    <div class="rso-form-container">
        <h3>Create New RSO</h3>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
        <form method="POST" class="rso-form" onsubmit="localStorage.setItem('activeTab', 'create-rso'); localStorage.setItem('tabOpen', 'true')">
            <div class="rso-row">
                <input type="text" name="rso_name" placeholder="RSO Name" required>
                <textarea name="rso_description" placeholder="RSO Description" required></textarea>
            </div>

            <h4>Admin Email</h4>
            <div class="email-wrapper">
                <input type="email" value="<?php echo htmlspecialchars($_SESSION['Email']); ?>" readonly>
            </div>

            <div id="email-container">
                <h4>Additional Member Emails (4 required)</h4>
                <input type="email" name="email1" placeholder="Member Email 1" required>
                <input type="email" name="email2" placeholder="Member Email 2" required>
                <input type="email" name="email3" placeholder="Member Email 3" required>
                <input type="email" name="email4" placeholder="Member Email 4" required>
            </div>

            <button type="submit" name="create_rso">Create RSO</button>
        </form>
    </div>
    </div>
    </div>
    </div>
    </div>

    <script>
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

        window.onload = function() {
            const tab = localStorage.getItem('activeTab');
            const tabOpen = localStorage.getItem('tabOpen');

            if (tab && tabOpen === 'true') {
                const section = document.getElementById(tab);
                if (section) section.style.display = 'block';
            }
        };
    </script>

    <script>
        let emailCount = 4;

        function addEmailField() {
            emailCount++;
            const container = document.getElementById('email-container');

            const wrapper = document.createElement('div');
            wrapper.className = 'email-wrapper';

            const inner = document.createElement('div');
            inner.style.display = 'flex';
            inner.style.gap = '8px';

            const input = document.createElement('input');
            input.type = 'email';
            input.name = `email${emailCount}`;
            input.placeholder = `Member Email ${emailCount}`;
            input.required = true;
            input.className = 'dynamic-email';
            input.style.flex = '1';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.textContent = 'Remove';
            removeBtn.style.background = '#c0392b';
            removeBtn.style.color = 'white';
            removeBtn.style.border = 'none';
            removeBtn.style.borderRadius = '4px';
            removeBtn.style.padding = '6px 10px';
            removeBtn.onclick = () => container.removeChild(wrapper);

            inner.appendChild(input);
            inner.appendChild(removeBtn);
            wrapper.appendChild(inner);
            container.appendChild(wrapper);
        }
    </script>

</body>
</html>