<?php
require_once __DIR__ . '/Includes/auth.php';
require_once __DIR__ . '/Includes/database.php';

// Require login before showing visitor list
require_login();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// --- date range filter defaults ---
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

if ($start_date !== '') {
    $start_date = date('Y-m-d', strtotime($start_date));
}
if ($end_date !== '') {
    $end_date = date('Y-m-d', strtotime($end_date));
}

if ($start_date === '' && $end_date === '') {
    // no dates supplied – default From to earliest visitor, To to today
    $earliest = '';
    if ($conn) {
        $r = $conn->query("SELECT MIN(created_at) AS earliest FROM visitor");
        if ($r) {
            $row = $r->fetch_assoc();
            $earliest = $row['earliest'] ?? '';
        }
    }
    if (!empty($earliest)) {
        try {
            $dt = new DateTime($earliest, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('Asia/Manila'));
            $start_date = $dt->format('Y-m-d');
        } catch (Exception $e) {
            $start_date = date('Y-m-d');
        }
    } else {
        $start_date = date('Y-m-d');
    }
    // always use today for the end date
    $end_date = date('Y-m-d');
} elseif ($start_date === '') {
    $start_date = $end_date;
} elseif ($end_date === '') {
    $end_date = $start_date;
}

$sd = new DateTime("$start_date 00:00:00", new DateTimeZone('Asia/Manila'));
$sd->setTimezone(new DateTimeZone('UTC'));
$sd_utc = $sd->format('Y-m-d H:i:s');

$ed = new DateTime("$end_date 23:59:59", new DateTimeZone('Asia/Manila'));
$ed->setTimezone(new DateTimeZone('UTC'));
$ed_utc = $ed->format('Y-m-d H:i:s');

$whereClause = " WHERE created_at >= '$sd_utc' AND created_at <= '$ed_utc'";

// pre-calc counts so we can show them above the table later
$active_count = 0;
$inactive_count = 0;
$total_count = 0;
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) + SUM(number_of_visitors) AS total FROM visitor{$whereClause}");
    if ($r) { $total_count = $r->fetch_assoc()['total'] ?? 0; }
    $r = $conn->query("SELECT COUNT(*) + SUM(number_of_visitors) AS total FROM visitor{$whereClause} AND (status='active' OR status IS NULL)");
    if ($r) { $active_count = $r->fetch_assoc()['total'] ?? 0; }
    $r = $conn->query("SELECT COUNT(*) + SUM(number_of_visitors) AS total FROM visitor{$whereClause} AND status='inactive'");
    if ($r) { $inactive_count = $r->fetch_assoc()['total'] ?? 0; }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Visitors - Hospital Visitors ID Recording System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-content">
        <h1>🏥 Hospital Visitors ID Recording System</h1>
        <nav class="header-nav">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="visitor.php" class="nav-link">Add Visitor</a>
            <a href="all_visitors.php" class="nav-link active">All Visitors</a>
            <?php if (is_logged_in()): ?>
                <a href="logout.php" class="nav-link">Logout</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="container">
    <?php
    // show flash when redirected after add
    if (isset($_GET['added']) && $_GET['added'] == '1') {
        echo "<div id='flash-msg' class='alert alert-success'>✅ New visitor added successfully!</div>";
    }
    ?>
    <div class="page-header">
        <h2>📋 All Visitors</h2>
        <p>View all registered visitors</p>
    </div>

    <!-- Visitor List Card -->
    <div class="card" id="list">
        <div class="card-header">
            <h3>📋 Registered Visitors</h3>
            <form method="GET" class="date-range-form" aria-label="Filter visitors by date range">
                <label><span class="cal-icon">📅</span> From&nbsp;<input type="date" name="start_date" aria-label="Start date" value="<?php echo htmlspecialchars($start_date); ?>"></label>
                <label><span class="cal-icon">📅</span> To&nbsp;<input type="date" name="end_date" aria-label="End date" value="<?php echo htmlspecialchars($end_date); ?>"></label>
                <button type="submit" class="btn" title="Apply date range">Apply</button>
                <?php if(isset($_GET['highlight'])): ?>
                    <input type="hidden" name="highlight" value="<?php echo htmlspecialchars($_GET['highlight']); ?>">
                <?php endif; ?>
                <a href="all_visitors.php" class="btn secondary" title="Clear filters">Reset</a>
            </form>
            <div class="range-summary-title">Summary for selected dates</div>
            <div class="range-stats">
                <div class="stat-card visitors">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $total_count; ?></div>
                    <div class="stat-label">Visitors</div>
                </div>
                <div class="stat-card checked-in">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $active_count; ?></div>
                    <div class="stat-label">Checked In</div>
                </div>
                <div class="stat-card inactive">
                    <div class="stat-icon">🚫</div>
                    <div class="stat-value"><?php echo $inactive_count; ?></div>
                    <div class="stat-label">Checked Out</div>
                </div>
            </div>
            <div style="position: relative;">
                <input type="text" id="searchInput" placeholder="🔍 Search by name, contact, or ID..." onkeyup="filterTable()">
                <ul id="searchSuggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; list-style: none; margin: 0; padding: 0; max-height: 300px; overflow-y: auto; display: none; z-index: 1000; border-radius: 0 0 8px 8px;">
                </ul>
            </div> 
        </div>
    <?php
    // hide success message after a few seconds (same behaviour as visitor.php)
    ?>
    <script>
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
    <?php
    // Query all visitors (apply date filter)
    if ($conn) {
    $sql = "SELECT * FROM visitor" . $whereClause . " ORDER BY visitor_id DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        echo "<div class='table-responsive'>";
        echo "<table class='visitor-table' id='visitorTable'>";
                echo "<thead>
                                <tr>
                                    <th style='text-align:center'>ID</th>
                                    <th style='text-align:center'>Full Name</th>
                                    <th style='text-align:center'>Contact Number</th>
                                    <th style='text-align:center'>Valid ID Type</th>
                                    <th style='text-align:center'>No. of Visitors</th>
                                    <th style='text-align:center'>Checked In</th>
                                    <th style='text-align:center'>Status</th>
                                    <th style='text-align:center'>Checked Out</th>
                                    <th style='text-align:center'>Actions</th>
                                </tr>
                            </thead>";
        echo "<tbody>";
                while ($row = $result->fetch_assoc()) {
                        $vid = htmlspecialchars($row['visitor_id']);
                        $vname = htmlspecialchars($row['full_name']);
                        $vcontact = htmlspecialchars($row['contact_number']);
                        $valid = htmlspecialchars($row['valid_id']);
                        $num_visitors = htmlspecialchars($row['number_of_visitors']);
                        $status = isset($row['status']) ? htmlspecialchars($row['status']) : 'active';
                        $visitor_id = $row['visitor_id'];

                        // format created_at to Philippines time
                        $created_display = '';
                        if (!empty($row['created_at'])) {
                                try {
                                        $dt = new DateTime($row['created_at'], new DateTimeZone('UTC'));
                                        $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                                        $created_display = $dt->format('n/j/Y') . '<br>' . $dt->format('g:i A');
                                } catch (Exception $e) {
                                        $created_display = htmlspecialchars($row['created_at']);
                                }
                        }

                        // Status icon
                        $status_icon = ($status === 'active') ? '🟢' : '🔴';

                        // Format inactive_at to Philippines time
                        $inactive_display = '';
                        if (!empty($row['inactive_at']) && $status === 'inactive') {
                                try {
                                        $dt = new DateTime($row['inactive_at'], new DateTimeZone('UTC'));
                                        $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                                        $inactive_display = $dt->format('n/j/Y') . '<br>' . $dt->format('g:i A');
                                } catch (Exception $e) {
                                        $inactive_display = '';
                                }
                        }

                        // Action button
                        $action_button = '';
                        if ($status === 'active') {
                            $action_button = "<button type='button' class='btn-inactive' data-id='$visitor_id' style='background: #ff6b6b; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500;'>Check Out</button>";
                        }

                        echo "<tr data-visitor-id='$visitor_id'>
                                        <td><span class='id-badge'>#$vid</span></td>
                                        <td style='text-align:center'><strong>$vname</strong></td>
                                        <td style='text-align:center'>$vcontact</td>
                                        <td style='text-align:center'><span class='id-type-badge'>$valid</span></td>
                                        <td style='text-align:center'><span class='visitor-count'>$num_visitors</span></td>
                                        <td style='text-align:center'>$created_display</td>
                                        <td style='text-align:center'><span class='status-icon' data-status='$status'>$status_icon</span></td>
                                        <td style='text-align:center'>$inactive_display</td>
                                        <td style='text-align:center'>
                                            $action_button
                                        </td>
                                    </tr>";
                }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        
        // total_count was already calculated before rendering the table
        echo "<div class='table-footer'>";
        echo "<p>Total: <strong>" . $total_count . "</strong> visitor(s) registered</p>";
        echo "</div>";
    } else {
        echo "<div class='empty-state'>";
        echo "<div class='empty-icon'>📭</div>";
        echo "<p>No visitors found for the selected date range ({$start_date} to {$end_date}).</p>";
        echo "<p class='empty-hint'>Go to Visitors page to add a new visitor</p>";
        echo "</div>";
    }

    $conn->close();
    }
    ?>
    </div>

    <script>
    // Highlight visitor if URL parameter is present and handle Inactive button
    document.addEventListener('DOMContentLoaded', function(){
        const urlParams = new URLSearchParams(window.location.search);
        let highlightId = urlParams.get('highlight') || '';
        // normalise: strip leading # if present
        if (highlightId.startsWith('#')) {
            highlightId = highlightId.slice(1);
        }
        
        if (highlightId) {
            const visitorTable = document.getElementById('visitorTable');
            const rows = visitorTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            Array.from(rows).forEach(row => {
                const firstCell = row.getElementsByTagName('td')[0];
                const cellId = firstCell ? firstCell.textContent.trim().replace(/^#/, '') : '';
                if (cellId === highlightId) {
                    row.style.backgroundColor = '#c8e6c9';
                    row.style.boxShadow = '0 0 15px rgba(76, 175, 80, 0.6)';
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Auto-remove highlight after 5 seconds
                    setTimeout(() => {
                        row.style.backgroundColor = '';
                        row.style.boxShadow = '';
                    }, 5000);
                }
            });
        }

        // Handle Inactive button clicks
        document.addEventListener('click', function(e){
            if (e.target.classList.contains('btn-inactive')) {
                const visitorId = e.target.getAttribute('data-id');
                const row = document.querySelector(`tr[data-visitor-id='${visitorId}']`);
                
                // Send AJAX request to update status
                fetch('update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'visitor_id=' + visitorId + '&status=inactive'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update status icon to red
                        const statusIcon = row.querySelector('.status-icon');
                        if (statusIcon) {
                            statusIcon.textContent = '🔴';
                            statusIcon.setAttribute('data-status', 'inactive');
                        }
                        
                        // Display the inactive date/time in the new column
                        const inactiveDateCell = row.querySelectorAll('td')[7]; // 8th column (0-indexed)
                        if (inactiveDateCell && data.inactive_at_display) {
                            inactiveDateCell.innerHTML = data.inactive_at_display;
                        }
                        
                        // Remove the Inactive button
                        const actionCell = row.querySelector('td:last-child');
                        if (actionCell) {
                            actionCell.innerHTML = '';
                        }
                    } else {
                        alert('Error updating status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating visitor status');
                });
            }
        });
    });

    // Dropdown search functionality - show all visitors on focus
    let allVisitorsCache = [];
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');

    function populateAllVisitors() {
        if (allVisitorsCache.length > 0) return;

        const visitorTable = document.getElementById('visitorTable');
        if (!visitorTable) return;
        
        const tbody = visitorTable.getElementsByTagName('tbody')[0];
        const rows = tbody.getElementsByTagName('tr');
        
        Array.from(rows).forEach(row => {
            const cells = row.getElementsByTagName('td');
            if (cells.length >= 2) {
                allVisitorsCache.push({
                    id: cells[0].textContent.trim().replace(/^#/, ''),
                    name: cells[1].textContent.trim(),
                    contact: cells[2].textContent.trim(),
                    dateTime: cells[5].textContent.trim(),
                    row: row
                });
            }
        });
    }

    searchInput.addEventListener('focus', function(){
        populateAllVisitors();
        displaySuggestions(allVisitorsCache);
    });

    function displaySuggestions(visitors) {
        searchSuggestions.innerHTML = '';
        const sorted = [...visitors].sort((a, b) => a.name.localeCompare(b.name));
        
        sorted.forEach(visitor => {
            const li = document.createElement('li');
            li.style.cssText = 'padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #eee; transition: background-color 0.2s;';
            li.onmouseover = function() { this.style.backgroundColor = '#f5f5f5'; };
            li.onmouseout = function() { this.style.backgroundColor = 'white'; };
            li.textContent = visitor.name + ' • ' + visitor.contact + ' • ' + visitor.dateTime;
            
            li.addEventListener('click', function(){
                searchInput.value = visitor.name;
                filterTable();
                searchSuggestions.innerHTML = '';
                searchSuggestions.style.display = 'none';
            });
            
            searchSuggestions.appendChild(li);
        });
        
        if (sorted.length > 0) {
            searchSuggestions.style.display = 'block';
        }
    }

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        populateAllVisitors();
        const visitorTable = document.getElementById('visitorTable');
        const tbody = visitorTable.getElementsByTagName('tbody')[0];
        
        if (!query) {
            displaySuggestions(allVisitorsCache);
            Array.from(tbody.getElementsByTagName('tr')).forEach(row => row.style.display = '');
            return;
        }

        const matches = allVisitorsCache.filter(visitor =>
            visitor.name.toLowerCase().includes(query) ||
            visitor.contact.toLowerCase().includes(query) ||
            visitor.id.toLowerCase().includes(query) ||
            visitor.dateTime.toLowerCase().includes(query)
        );

        displaySuggestions(matches);
        
        Array.from(tbody.getElementsByTagName('tr')).forEach(row => {
            row.style.display = matches.some(m => m.row === row) ? '' : 'none';
        });
    }

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e){
        if (e.target !== searchInput && !searchSuggestions.contains(e.target)) {
            searchSuggestions.style.display = 'none';
        }
    });
    </script>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Hospital Visitors ID Recording System • All Visitors
</footer>

</body>
</html>
