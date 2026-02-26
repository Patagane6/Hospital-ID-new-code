<?php require_once __DIR__ . '/Includes/database.php'; 

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Fetch dashboard statistics
$stats = [
    'total_visitors' => 0,
    'today_visitors' => 0,
    'active_visits' => 0,
    'inactive_visitors' => 0,
    'total_visits' => 0
];

if ($conn) {
$result = $conn->query("SELECT COUNT(*) + SUM(number_of_visitors) AS total FROM visitor");
if ($result) {
    $stats['total_visitors'] = $result->fetch_assoc()['total'];
}

// Count today's visitors (based on Philippines date)
// Today in Philippines timezone
$today_ph = date('Y-m-d'); // e.g., 2026-02-26
// Convert to UTC boundaries: Philippines is UTC+8, so subtract 8 hours for UTC
$today_utc_start = date('Y-m-d H:i:s', strtotime("$today_ph 00:00:00") - 8*3600); // Start of today in UTC
$today_utc_end = date('Y-m-d H:i:s', strtotime("$today_ph 23:59:59") - 8*3600);   // End of today in UTC
$result = $conn->query("SELECT COUNT(*) + SUM(number_of_visitors) AS total FROM visitor WHERE created_at >= '$today_utc_start' AND created_at <= '$today_utc_end'");
if ($result) {
    $today_row = $result->fetch_assoc();
    $stats['today_visitors'] = $today_row['total'] ?? 0;
}

$result = $conn->query("SELECT COUNT(*) as total FROM visit_log");
if ($result) {
    $stats['total_visits'] = $result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT COUNT(*) + SUM(number_of_visitors) AS total FROM visitor WHERE status = 'active' OR status IS NULL");
if ($result) {
    $stats['active_visits'] = $result->fetch_assoc()['total'];
}

// count inactive visitors
$result = $conn->query("SELECT COUNT(*) + SUM(number_of_visitors) AS total FROM visitor WHERE status = 'inactive'");
if ($result) {
    $stats['inactive_visitors'] = $result->fetch_assoc()['total'];
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hospital Visitor System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-content">
        <h1>🏥 Hospital Visitor System</h1>
        <nav class="header-nav">
            <a href="index.php" class="nav-link active">Dashboard</a>
            <a href="visitor.php" class="nav-link">Add Visitor</a>
            <a href="all_visitors.php" class="nav-link">All Visitors</a>
        </nav>
    </div>
</header>

<div class="dashboard-container">
    <!-- KPI Cards Grid -->
    <div class="kpi-grid" style="max-width:600px; margin:0 auto;">
        <div class="kpi-card">
            <div class="kpi-icon">👥</div>
            <div class="kpi-content">
                <div class="kpi-value"><?php echo $stats['total_visitors']; ?></div>
                <div class="kpi-label">Total Visitors</div>
            </div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-icon">✅</div>
            <div class="kpi-content">
                <div class="kpi-value"><?php echo $stats['active_visits']; ?></div>
                <div class="kpi-label">Active Visitors</div>
            </div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-icon">🚫</div>
            <div class="kpi-content">
                <div class="kpi-value"><?php echo $stats['inactive_visitors']; ?></div>
                <div class="kpi-label">Inactive Visitors</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <p style="color:#546e7a; font-size:0.9rem; margin-bottom:12px;">Use the cards below to add, view or search visitors quickly.</p>
        <div class="action-grid">
            <a href="visitor.php" class="action-card">
                <div class="action-icon">➕</div>
                <div class="action-title">Add New Visitor</div>
                <div class="action-desc">Register a new visitor</div>
            </a>
            
            <a href="all_visitors.php" class="action-card">
                <div class="action-icon">📋</div>
                <div class="action-title">View All Visitors</div>
                <div class="action-desc">Browse visitor records</div>
            </a>
            
            <button id="searchBtn" class="action-card" style="border: none; background: none; cursor: pointer; padding: 0;">
                <div class="action-icon">🔍</div>
                <div class="action-title">Search Visitor</div>
                <div class="action-desc">Find visitor by name or ID</div>
            </button>
        </div>
    </div>

    <!-- Search Modal -->
    <div id="searchModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; padding-top: 100px;">
        <div style="background: white; margin: auto; padding: 30px; border-radius: 8px; max-width: 500px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">🔍 Search Visitor</h3>
                <button id="closeSearchBtn" style="border: none; background: none; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">✕</button>
            </div>
            <div style="position: relative;">
                <input type="text" id="dashboardSearch" placeholder="Search by name, contact, date/time..." 
                       style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                <ul id="dashboardSuggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; list-style: none; margin: 0; padding: 0; max-height: 300px; overflow-y: auto; display: none; z-index: 1000; border-radius: 0 0 8px 8px; width: 100%;">
                </ul>
            </div>
        </div>
    </div>

    </div>

<footer>
    &copy; <?php echo date("Y"); ?> Hospital Visitor System
</footer>

<script>
    // Search modal functionality
    const searchBtn = document.getElementById('searchBtn');
    const searchModal = document.getElementById('searchModal');
    const closeSearchBtn = document.getElementById('closeSearchBtn');
    const dashboardSearch = document.getElementById('dashboardSearch');
    const dashboardSuggestions = document.getElementById('dashboardSuggestions');
    let allVisitors = [];

    // Open search modal
    searchBtn.addEventListener('click', function(){
        searchModal.style.display = 'block';
        dashboardSearch.focus();
        fetchVisitors();
    });

    // Close search modal
    closeSearchBtn.addEventListener('click', function(){
        searchModal.style.display = 'none';
        dashboardSearch.value = '';
        dashboardSuggestions.innerHTML = '';
    });

    // Close modal when clicking outside
    searchModal.addEventListener('click', function(e){
        if (e.target === searchModal) {
            searchModal.style.display = 'none';
            dashboardSearch.value = '';
            dashboardSuggestions.innerHTML = '';
        }
    });

    // Fetch all visitors from database
    function fetchVisitors() {
        if (allVisitors.length > 0) return; // Already loaded
        
        fetch('get_visitors.php')
            .then(response => response.json())
            .then(data => {
                allVisitors = data;
            })
            .catch(error => console.error('Error fetching visitors:', error));
    }

    // Autocomplete search
    dashboardSearch.addEventListener('input', function(){
        const query = this.value.toLowerCase().trim();
        dashboardSuggestions.innerHTML = '';

        if (!query) {
            dashboardSuggestions.style.display = 'none';
            return;
        }

        // Match visitors by name, contact, ID, or date that start with the typed query (case-insensitive)
        const matches = allVisitors.filter(visitor => 
            visitor.name.toLowerCase().startsWith(query) ||
            visitor.contact.toLowerCase().startsWith(query) ||
            visitor.id.toLowerCase().startsWith(query) ||
            visitor.dateTime.toLowerCase().startsWith(query)
        );

        if (matches.length === 0) {
            dashboardSuggestions.style.display = 'none';
            return;
        }

            // Sort matches alphabetically by visitor name (A-Z)
            matches.sort((a, b) => a.name.localeCompare(b.name));

        matches.slice(0, 8).forEach(visitor => {
            const li = document.createElement('li');
            li.style.cssText = 'padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #eee;';
                li.textContent = visitor.name + ' • ' + visitor.contact + ' • ' + visitor.dateTime;
            
            li.addEventListener('click', function(){
                // Navigate to all_visitors page with highlight parameter
                window.location.href = 'all_visitors.php?highlight=' + visitor.id;
            });

            dashboardSuggestions.appendChild(li);
        });

        dashboardSuggestions.style.display = 'block';
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e){
        if (e.target !== dashboardSearch && !dashboardSuggestions.contains(e.target)) {
            dashboardSuggestions.style.display = 'none';
        }
    });
</script>
