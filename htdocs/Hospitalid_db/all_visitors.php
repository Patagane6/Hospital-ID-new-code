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
                        $delete_id = urlencode($row['visitor_id']);

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

                        echo "<tr>
                                        <td><span class='id-badge'>#$vid</span></td>
                                        <td style='text-align:center'><strong>$vname</strong></td>
                                        <td style='text-align:center'>$vcontact</td>
                                        <td style='text-align:center'><span class='id-type-badge'>$valid</span></td>
                                        <td style='text-align:center'><span class='visitor-count'>$num_visitors</span></td>
                                        <td style='text-align:center'>$created_display</td>
                                        <td style='text-align:center'>
                                            <a href='all_visitors.php?delete=$delete_id' class='btn-delete' 
                                                 onclick=\"return confirm('Are you sure you want to delete $vname?');\">
                                                 🗑️ Delete
                                            </a>
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
    &copy; <?php echo date("Y"); ?> Hospital Visitor System • All Visitors
</footer>

</body>
</html>
