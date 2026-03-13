<?php
require_once __DIR__ . '/Includes/auth.php';
require_once __DIR__ . '/Includes/database.php';

require_login();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Show all visitors on this page (date range controls were removed).
$whereClause = "";

// pre-calc counts so we can show them above the table later
$active_count = 0;
$inactive_count = 0;
$total_count = 0;
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) AS total FROM visitor{$whereClause}");
    if ($r) { $total_count = $r->fetch_assoc()['total'] ?? 0; }
    $r = $conn->query("SELECT COUNT(*) AS total FROM visitor{$whereClause} AND (status='active' OR status IS NULL)");
    if ($r) { $active_count = $r->fetch_assoc()['total'] ?? 0; }
    $r = $conn->query("SELECT COUNT(*) AS total FROM visitor{$whereClause} AND status='inactive'");
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
            <a href="logout.php" class="nav-link logout-link">Logout</a>
        </nav>
    </div>
</header>

<div id="logoutModal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="logoutModalTitle">
    <div class="modal-content">
        <h3 id="logoutModalTitle">You are about to log out</h3>
        <p>Are you sure you want to end your session?</p>
        <div class="modal-actions">
            <button id="logoutConfirm" class="btn danger">Log Out</button>
            <button id="logoutCancel" class="btn secondary">Cancel</button>
        </div>
    </div>
</div>

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
            <div class="range-summary-title">Summary</div>
            <div class="range-stats">
                <div class="stat-card visitors">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value" id="summaryTotalCount"><?php echo $total_count; ?></div>
                    <div class="stat-label">Visitors</div>
                </div>
                <div class="stat-card checked-in">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value" id="summaryActiveCount"><?php echo $active_count; ?></div>
                    <div class="stat-label">Checked In</div>
                </div>
                <div class="stat-card inactive">
                    <div class="stat-icon">🚫</div>
                    <div class="stat-value" id="summaryInactiveCount"><?php echo $inactive_count; ?></div>
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
        $visitor_groups = [];
                while ($row = $result->fetch_assoc()) {
            $vid = htmlspecialchars($row['visitor_id']);
            $vname = htmlspecialchars($row['full_name']);
            $vcontact_raw = trim((string) ($row['contact_number'] ?? ''));
            $vcontact = $vcontact_raw !== '' ? htmlspecialchars($vcontact_raw) : '<span class="muted-value">No contact</span>';
            $status = isset($row['status']) ? htmlspecialchars($row['status']) : 'active';
            $visitor_id = $row['visitor_id'];

            $group_key = 'unknown-date';
            $group_label = 'Unknown Date';
            $created_display = '';
            if (!empty($row['created_at'])) {
                try {
                    $dt = new DateTime($row['created_at'], new DateTimeZone('UTC'));
                    $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                    $group_key = $dt->format('Y-m-d');
                    $group_label = $dt->format('F j, Y');
                    $created_display = $dt->format('n/j/Y') . '<br>' . $dt->format('g:i A');
                } catch (Exception $e) {
                    $created_display = htmlspecialchars($row['created_at']);
                }
            }

            if (!isset($visitor_groups[$group_key])) {
                $visitor_groups[$group_key] = [
                    'label' => $group_label,
                    'total' => 0,
                    'checked_in' => 0,
                    'checked_out' => 0,
                    'rows' => []
                ];
            }

            $status_icon = ($status === 'active') ? '🟢' : '🔴';

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

            $action_button = '';
            if ($status === 'active') {
                $action_button = "<button type='button' class='btn-inactive' data-id='$visitor_id' style='background: #ff6b6b; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500;'>Check Out</button>";
            }

            $visitor_groups[$group_key]['total']++;
            if ($status === 'inactive') {
                $visitor_groups[$group_key]['checked_out']++;
            } else {
                $visitor_groups[$group_key]['checked_in']++;
            }

            $visitor_groups[$group_key]['rows'][] = "<tr class='visitor-row' data-visitor-id='$visitor_id'>
                                <td><span class='id-badge'>#$vid</span></td>
                                <td style='text-align:center'><strong>$vname</strong></td>
                                <td style='text-align:center'>$vcontact</td>
                                <td style='text-align:center'>$created_display</td>
                                <td style='text-align:center'><span class='status-icon' data-status='$status'>$status_icon</span></td>
                                <td style='text-align:center'>$inactive_display</td>
                                <td style='text-align:center'>$action_button</td>
                            </tr>";
                }

        echo "<div class='visitor-groups' id='visitorGroups'>";
        $is_first_group = true;
        foreach ($visitor_groups as $group_key => $group) {
            $safe_group_key = htmlspecialchars($group_key);
            $safe_group_label = htmlspecialchars($group['label']);
            $expanded = $is_first_group ? 'true' : 'false';
            $hidden_attr = $is_first_group ? '' : ' hidden';

            echo "<section class='date-group' data-date-group='$safe_group_key'>";
            echo "<button type='button' class='date-group-toggle' aria-expanded='$expanded'>
                    <span class='date-group-title-wrap'>
                        <span class='date-group-icon'>🗓️</span>
                        <span>
                            <span class='date-group-title'>$safe_group_label</span>
                            <span class='date-group-subtitle'>{$group['total']} visitor(s)</span>
                        </span>
                    </span>
                    <span class='date-group-metrics'>
                        <span class='date-chip checked-in'>In {$group['checked_in']}</span>
                        <span class='date-chip checked-out'>Out {$group['checked_out']}</span>
                        <span class='date-group-caret' aria-hidden='true'>▾</span>
                    </span>
                  </button>";

            echo "<div class='date-group-body'$hidden_attr>";
            echo "<div class='table-responsive'>";
            echo "<table class='visitor-table'>";
            echo "<thead>
                    <tr>
                        <th style='text-align:center'>ID</th>
                        <th style='text-align:center'>Full Name</th>
                        <th style='text-align:center'>Contact Number</th>
                        <th style='text-align:center'>Checked In</th>
                        <th style='text-align:center'>Status</th>
                        <th style='text-align:center'>Checked Out</th>
                        <th style='text-align:center'>Actions</th>
                    </tr>
                  </thead>";
            echo "<tbody>" . implode('', $group['rows']) . "</tbody>";
            echo "</table>";
            echo "</div>";
            echo "</div>";
            echo "</section>";

            $is_first_group = false;
        }
        echo "</div>";
        
        // total_count was already calculated before rendering the table
        echo "<div class='table-footer'>";
        echo "<p>Total: <strong>" . $total_count . "</strong> visitor(s) registered</p>";
        echo "</div>";
    } else {
        echo "<div class='empty-state'>";
        echo "<div class='empty-icon'>📭</div>";
        echo "<p>No visitors found.</p>";
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
        const dateGroups = Array.from(document.querySelectorAll('.date-group'));

        function setGroupExpanded(group, expanded) {
            if (!group) return;
            const toggle = group.querySelector('.date-group-toggle');
            const body = group.querySelector('.date-group-body');
            if (!toggle || !body) return;

            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            body.hidden = !expanded;
        }

        function updateSummaryCounts() {
            const rows = Array.from(document.querySelectorAll('.visitor-row'));
            const total = rows.length;
            const checkedOut = rows.filter(row => {
                const icon = row.querySelector('.status-icon');
                return icon && icon.getAttribute('data-status') === 'inactive';
            }).length;
            const checkedIn = total - checkedOut;

            const totalEl = document.getElementById('summaryTotalCount');
            const activeEl = document.getElementById('summaryActiveCount');
            const inactiveEl = document.getElementById('summaryInactiveCount');

            if (totalEl) totalEl.textContent = total;
            if (activeEl) activeEl.textContent = checkedIn;
            if (inactiveEl) inactiveEl.textContent = checkedOut;
        }

        function updateGroupCounts(group) {
            if (!group) return;
            const rows = Array.from(group.querySelectorAll('.visitor-row'));
            const total = rows.length;
            const checkedOut = rows.filter(row => {
                const icon = row.querySelector('.status-icon');
                return icon && icon.getAttribute('data-status') === 'inactive';
            }).length;
            const checkedIn = total - checkedOut;

            const subtitle = group.querySelector('.date-group-subtitle');
            const checkedInChip = group.querySelector('.date-chip.checked-in');
            const checkedOutChip = group.querySelector('.date-chip.checked-out');

            if (subtitle) subtitle.textContent = `${total} visitor(s)`;
            if (checkedInChip) checkedInChip.textContent = `In ${checkedIn}`;
            if (checkedOutChip) checkedOutChip.textContent = `Out ${checkedOut}`;
        }

        dateGroups.forEach(group => {
            const toggle = group.querySelector('.date-group-toggle');
            if (!toggle) return;

            toggle.addEventListener('click', function(){
                const expanded = toggle.getAttribute('aria-expanded') === 'true';
                setGroupExpanded(group, !expanded);
            });
        });

        // normalise: strip leading # if present
        if (highlightId.startsWith('#')) {
            highlightId = highlightId.slice(1);
        }
        
        if (highlightId) {
            const row = document.querySelector(`tr[data-visitor-id='${highlightId}']`);
            if (row) {
                setGroupExpanded(row.closest('.date-group'), true);
                row.style.backgroundColor = '#c8e6c9';
                row.style.boxShadow = '0 0 15px rgba(76, 175, 80, 0.6)';
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });

                setTimeout(() => {
                    row.style.backgroundColor = '';
                    row.style.boxShadow = '';
                }, 5000);
            }
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
                        const inactiveDateCell = row.querySelectorAll('td')[5]; // 6th column (0-indexed)
                        if (inactiveDateCell && data.inactive_at_display) {
                            inactiveDateCell.innerHTML = data.inactive_at_display;
                        }
                        
                        // Remove the Inactive button
                        const actionCell = row.querySelector('td:last-child');
                        if (actionCell) {
                            actionCell.innerHTML = '';
                        }

                        updateGroupCounts(row.closest('.date-group'));
                        updateSummaryCounts();
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

    function updateVisibleDateGroups() {
        document.querySelectorAll('.date-group').forEach(group => {
            const rows = Array.from(group.querySelectorAll('.visitor-row'));
            const hasVisibleRows = rows.some(row => row.style.display !== 'none');
            group.style.display = hasVisibleRows ? '' : 'none';
        });
    }

    function populateAllVisitors() {
        if (allVisitorsCache.length > 0) return;

        const rows = document.querySelectorAll('.visitor-row');
        Array.from(rows).forEach(row => {
            const cells = row.getElementsByTagName('td');
            if (cells.length >= 2) {
                allVisitorsCache.push({
                    id: cells[0].textContent.trim().replace(/^#/, ''),
                    name: cells[1].textContent.trim(),
                    contact: cells[2].textContent.trim(),
                    dateTime: cells[3].textContent.trim(),
                    row: row,
                    group: row.closest('.date-group')
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
            const contactText = visitor.contact || 'No contact';
            li.textContent = visitor.name + ' • ' + contactText + ' • ' + visitor.dateTime;
            
            li.addEventListener('click', function(){
                searchInput.value = visitor.name;
                filterTable();
                const matchingRow = visitor.row;
                const matchingGroup = visitor.group;
                if (matchingGroup) {
                    const toggle = matchingGroup.querySelector('.date-group-toggle');
                    const body = matchingGroup.querySelector('.date-group-body');
                    if (toggle && body) {
                        toggle.setAttribute('aria-expanded', 'true');
                        body.hidden = false;
                    }
                }
                if (matchingRow) {
                    matchingRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                searchSuggestions.innerHTML = '';
                searchSuggestions.style.display = 'none';
            });
            
            searchSuggestions.appendChild(li);
        });
        
        if (sorted.length > 0) {
            searchSuggestions.style.display = 'block';
        } else {
            searchSuggestions.style.display = 'none';
        }
    }

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        populateAllVisitors();
        
        if (!query) {
            displaySuggestions(allVisitorsCache);
            allVisitorsCache.forEach(visitor => {
                visitor.row.style.display = '';
            });
            document.querySelectorAll('.date-group').forEach((group, index) => {
                group.style.display = '';
                const toggle = group.querySelector('.date-group-toggle');
                const body = group.querySelector('.date-group-body');
                if (toggle && body) {
                    const shouldExpand = index === 0;
                    toggle.setAttribute('aria-expanded', shouldExpand ? 'true' : 'false');
                    body.hidden = !shouldExpand;
                }
            });
            return;
        }

        const matches = allVisitorsCache.filter(visitor =>
            visitor.name.toLowerCase().includes(query) ||
            visitor.contact.toLowerCase().includes(query) ||
            visitor.id.toLowerCase().includes(query) ||
            visitor.dateTime.toLowerCase().includes(query)
        );

        displaySuggestions(matches);

        allVisitorsCache.forEach(visitor => {
            const matched = matches.some(match => match.row === visitor.row);
            visitor.row.style.display = matched ? '' : 'none';
            if (matched && visitor.group) {
                const toggle = visitor.group.querySelector('.date-group-toggle');
                const body = visitor.group.querySelector('.date-group-body');
                if (toggle && body) {
                    toggle.setAttribute('aria-expanded', 'true');
                    body.hidden = false;
                }
            }
        });

        updateVisibleDateGroups();
    }

    function pollForNewVisitors() {
        if (document.hidden || (searchInput && searchInput.value.trim() !== '')) {
            return;
        }

        fetch('get_visitors.php?ts=' + Date.now(), { cache: 'no-store' })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Request failed');
                }
                return response.json();
            })
            .then(data => {
                if (!Array.isArray(data)) return;

                const serverIds = data
                    .map(v => String(v.id))
                    .sort((a, b) => Number(a) - Number(b))
                    .join(',');

                const localIds = Array.from(document.querySelectorAll('.visitor-row'))
                    .map(row => String(row.getAttribute('data-visitor-id')))
                    .sort((a, b) => Number(a) - Number(b))
                    .join(',');

                if (serverIds !== localIds) {
                    window.location.reload();
                }
            })
            .catch(() => {
                // Ignore transient polling errors.
            });
    }

    setInterval(pollForNewVisitors, 8000);

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e){
        if (e.target !== searchInput && !searchSuggestions.contains(e.target)) {
            searchSuggestions.style.display = 'none';
        }
    });
    </script>

<script>
(function(){
    const logoutLinks = document.querySelectorAll('.logout-link');
    const modal = document.getElementById('logoutModal');
    const confirmBtn = document.getElementById('logoutConfirm');
    const cancelBtn = document.getElementById('logoutCancel');

    if (!modal || logoutLinks.length === 0 || !confirmBtn || !cancelBtn) return;

    function showModal() {
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    }

    function hideModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    }

    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e){
            e.preventDefault();
            showModal();
        });
    });

    cancelBtn.addEventListener('click', hideModal);
    modal.addEventListener('click', function(e){
        if (e.target === modal) hideModal();
    });

    confirmBtn.addEventListener('click', function(){
        window.location.href = 'logout.php';
    });
})();
</script>

</div>

<footer>
    &copy; <?php echo date("Y"); ?> Hospital Visitors ID Recording System • All Visitors
</footer>

</body>
</html>
