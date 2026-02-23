<?php ob_start(); require_once __DIR__ . '/Includes/database.php'; ?>

<?php

$add_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_visitor']) && $conn) {
    $full_name = $conn->real_escape_string(trim($_POST['full_name'] ?? ''));

    // sanitize contact number: keep digits only and validate (must be exactly 11 digits)
    $contact_number_raw = $_POST['contact_number'] ?? '';
    $contact_number_digits = preg_replace('/\D+/', '', $contact_number_raw);
    if ($contact_number_digits === '') {
        $add_error = 'Contact number must contain digits only.';
    } elseif (strlen($contact_number_digits) !== 11) {
        $add_error = $add_error ?: 'Contact number must be exactly 11 digits.';
    }
    $contact_number = $conn->real_escape_string($contact_number_digits);

    // allowed ID types
    $allowed_ids = [
        'Passport',
        "Driver's License",
        'Voter ID',
        'SSS/GSIS ID',
        'PhilHealth ID',
        'Senior Citizen ID',
        'Student ID',
        'Other'
    ];
    $valid_id_raw = $_POST['valid_id'] ?? '';
    if (!in_array($valid_id_raw, $allowed_ids, true)) {
        $add_error = $add_error ?: 'Invalid ID type selected.';
    }
    $valid_id = $conn->real_escape_string($valid_id_raw);

    $number_of_visitors = intval($_POST['number_of_visitors'] ?? 0);

    if ($add_error === '') {
        // Use prepared statement for security
        $stmt = $conn->prepare("INSERT INTO visitor (full_name, contact_number, valid_id, number_of_visitors) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $full_name, $contact_number, $valid_id, $number_of_visitors);
        
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: visitor.php?added=1');
            exit;
        } else {
            $add_error = $stmt->error;
            $stmt->close();
        }
    }
}

// Handle delete action early so we can redirect before HTML is sent
if (isset($_GET['delete']) && $conn) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        $conn->query("DELETE FROM visitor WHERE visitor_id = $id");
    }
    header('Location: visitor.php?deleted=1');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Visitors - Hospital Visitor System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-content">
        <h1>🏥 Hospital Visitor System</h1>
        <nav class="header-nav">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="visitor.php" class="nav-link active">Visitors</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <h2>👥 Visitor Management</h2>
        <p>Register and manage hospital visitors</p>
    </div>

    <!-- Add Visitor Card -->
    <div class="card form-card">
        <h3>➕ Register New Visitor</h3>
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name" placeholder="Enter full name" required>
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" 
                           placeholder="09XXXXXXXXX" inputmode="numeric" 
                           pattern="\d{11}" maxlength="11" minlength="11" 
                           title="Enter exactly 11 digits" required>
                </div>

                <div class="form-group">
                    <label for="valid_id">Type of Valid ID</label>
                    <select name="valid_id" id="valid_id" required>
                        <option value="">-- Select ID Type --</option>
                        <option>Passport</option>
                        <option>Driver's License</option>
                        <option>Voter ID</option>
                        <option>SSS/GSIS ID</option>
                        <option>PhilHealth ID</option>
                        <option>Senior Citizen ID</option>
                        <option>Student ID</option>
                        <option>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="number_of_visitors">Number of Visitors</label>
                    <input type="number" name="number_of_visitors" id="number_of_visitors" 
                           min="1" placeholder="Enter number" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="add_visitor" class="btn">Add Visitor</button>
                <button type="reset" class="btn secondary">Clear Form</button>
            </div>
        </form>

        <?php
        // show flash messages
        if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
            echo "<div id='flash-msg' class='alert alert-success'>✅ Visitor record deleted successfully.</div>";
        }
        if (isset($_GET['added']) && $_GET['added'] == '1') {
            echo "<div id='flash-msg' class='alert alert-success'>✅ New visitor added successfully!</div>";
        }
        if (!empty($add_error)) {
            echo "<div id='flash-msg' class='alert alert-error'>❌ Error: " . htmlspecialchars($add_error) . "</div>";
        }
        ?>
    </div>

    <script>
    // hide the message after 3 seconds and remove query string to avoid repeat
    setTimeout(function(){
        var el = document.getElementById('flash-msg');
        if (el) {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(function(){ el.remove(); }, 500);
        }
        if (window.history && window.history.replaceState) {
            var url = window.location.protocol + '//' + window.location.host + window.location.pathname;
            window.history.replaceState({}, document.title, url);
        }
    }, 3000);
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function(){
        var cn = document.getElementById('contact_number');
        if (cn) {
            // remove non-digits on input (handles typing and paste) and limit to 11 digits
            cn.addEventListener('input', function(){
                this.value = this.value.replace(/\D/g,'').slice(0,11);
            });

            // optionally block most non-numeric key presses while allowing navigation/editing
            cn.addEventListener('keydown', function(e){
                var allowed = [8,9,13,27,37,38,39,40,46]; // backspace, tab, enter, esc, arrows, delete
                if (allowed.indexOf(e.keyCode) !== -1) return;
                if (e.ctrlKey || e.metaKey) return; // allow Ctrl/Cmd shortcuts
                // digits (top row) and numpad
                if ((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105)) return;
                e.preventDefault();
            });
        }
    });
    </script>

    <!-- Visitor List Card -->
    <div class="card" id="list">
        <div class="card-header">
            <h3>📋 Registered Visitors</h3>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search by name, contact, or ID..." onkeyup="filterTable()">
            </div>
        </div>
    <?php
    // Query all visitors
    if ($conn) {
    $sql = "SELECT * FROM visitor ORDER BY visitor_id DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        echo "<div class='table-responsive'>";
        echo "<table class='visitor-table' id='visitorTable'>";
        echo "<thead>
                <tr>
                  <th>ID</th>
                  <th>Full Name</th>
                  <th>Contact Number</th>
                  <th>Valid ID Type</th>
                  <th>No. of Visitors</th>
                  <th>Actions</th>
                </tr>
              </thead>";
        echo "<tbody>";
        while ($row = $result->fetch_assoc()) {
            $vid = htmlspecialchars($row['visitor_id']);
            $vname = htmlspecialchars($row['full_name']);
            $vcontact = htmlspecialchars($row['contact_number']);
            $valid = htmlspecialchars($row['valid_id']);
            $num_visitors = htmlspecialchars($row['number_of_visitors']);
            $delete_id = urlencode($row['visitor_id']);
            echo "<tr>
                    <td><span class='id-badge'>#$vid</span></td>
                    <td><strong>$vname</strong></td>
                    <td>$vcontact</td>
                    <td><span class='id-type-badge'>$valid</span></td>
                    <td><span class='visitor-count'>$num_visitors</span></td>
                    <td>
                      <a href='visitor.php?delete=$delete_id' class='btn-delete' 
                         onclick=\"return confirm('Are you sure you want to delete $vname?');\">
                         🗑️ Delete
                      </a>
                    </td>
                  </tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        
        echo "<div class='table-footer'>";
        echo "<p>Total: <strong>" . $result->num_rows . "</strong> visitor(s) registered</p>";
        echo "</div>";
    } else {
        echo "<div class='empty-state'>";
        echo "<div class='empty-icon'>📭</div>";
        echo "<p>No visitors registered yet</p>";
        echo "<p class='empty-hint'>Add your first visitor using the form above</p>";
        echo "</div>";
    }

    $conn->close();
    }
    ?>
    </div>

    <script>
    // Real-time table search/filter
    function filterTable() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toUpperCase();
        const table = document.getElementById('visitorTable');
        const tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            const row = tr[i];
            let found = false;
            const td = row.getElementsByTagName('td');
            
            for (let j = 0; j < td.length - 1; j++) { // exclude action column
                if (td[j]) {
                    const txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
    }
    </script>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Hospital Visitor System • Visitor Management
</footer>

</body>
</html>
