<?php require_once __DIR__ . '/Includes/database.php'; 

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Visitors - Hospital Visitor System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-content">
        <h1>🏥 Hospital Visitor System</h1>
        <nav class="header-nav">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="visitor.php" class="nav-link">Add Visitor</a>
            <a href="all_visitors.php" class="nav-link active">All Visitors</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <h2>📋 All Visitors</h2>
        <p>View all registered visitors</p>
    </div>

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
                                    <th style='text-align:center'>ID</th>
                                    <th style='text-align:center'>Full Name</th>
                                    <th style='text-align:center'>Contact Number</th>
                                    <th style='text-align:center'>Valid ID Type</th>
                                    <th style='text-align:center'>No. of Visitors</th>
                                    <th style='text-align:center'>Date &amp; Time</th>
                                    <th style='text-align:center'>Status</th>
                                    <th style='text-align:center'>Inactive Date &amp; Time</th>
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
                                        $dt = new DateTime($row['created_at']);
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
                                        $dt = new DateTime($row['inactive_at']);
                                        $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                                        $inactive_display = $dt->format('n/j/Y') . '<br>' . $dt->format('g:i A');
                                } catch (Exception $e) {
                                        $inactive_display = '';
                                }
                        }

                        // Action button
                        $action_button = '';
                        if ($status === 'active') {
                            $action_button = "<button type='button' class='btn-inactive' data-id='$visitor_id' style='background: #ff6b6b; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500;'>Inactive</button>";
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
        
        // Calculate total visitors including number_of_visitors field
        $total_result = $conn->query("SELECT COUNT(*) + SUM(number_of_visitors) AS total FROM visitor");
        $total_count = 0;
        if ($total_result) {
            $total_count = $total_result->fetch_assoc()['total'];
        }
        
        echo "<div class='table-footer'>";
        echo "<p>Total: <strong>" . $total_count . "</strong> visitor(s) registered</p>";
        echo "</div>";
    } else {
        echo "<div class='empty-state'>";
        echo "<div class='empty-icon'>📭</div>";
        echo "<p>No visitors registered yet</p>";
        echo "<p class='empty-hint'>Go to Visitors page to add your first visitor</p>";
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
        const highlightId = urlParams.get('highlight');
        
        if (highlightId) {
            const visitorTable = document.getElementById('visitorTable');
            const rows = visitorTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            Array.from(rows).forEach(row => {
                const firstCell = row.getElementsByTagName('td')[0];
                if (firstCell.textContent.trim() === highlightId) {
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
    </script>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Hospital Visitor System • All Visitors
</footer>

</body>
</html>
