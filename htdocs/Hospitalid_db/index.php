<?php require_once __DIR__ . '/Includes/database.php'; 

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Fetch dashboard statistics
$stats = [
    'total_visitors' => 0,
    'today_visitors' => 0,
    'active_visits' => 0,
    'total_visits' => 0
];

if ($conn) {
$result = $conn->query("SELECT COUNT(*) + SUM(number_of_visitors) AS total FROM visitor");
if ($result) {
    $stats['total_visitors'] = $result->fetch_assoc()['total'];
}

// Count today's visitors (based on Philippines date)
$today_start = date('Y-m-d 00:00:00', strtotime('-8 hours')); // Start of today in Philippines time, expressed in UTC
$today_end = date('Y-m-d 23:59:59', strtotime('-8 hours'));   // End of today in Philippines time, expressed in UTC
$result = $conn->query("SELECT COUNT(*) + SUM(number_of_visitors) AS total FROM visitor WHERE created_at >= '$today_start' AND created_at <= '$today_end'");
if ($result) {
    $stats['today_visitors'] = $result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT COUNT(*) as total FROM visit_log");
if ($result) {
    $stats['total_visits'] = $result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT COUNT(*) as total FROM visit_log WHERE check_in_time IS NOT NULL AND check_out_time IS NULL");
if ($result) {
    $stats['active_visits'] = $result->fetch_assoc()['total'];
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
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon">👥</div>
            <div class="kpi-content">
                <div class="kpi-value"><?php echo $stats['total_visitors']; ?></div>
                <div class="kpi-label">Total Visitors</div>
            </div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-icon">📅</div>
            <div class="kpi-content">
                <div class="kpi-value"><?php echo $stats['today_visitors']; ?></div>
                <div class="kpi-label">Today's Visitors</div>
            </div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-icon">✅</div>
            <div class="kpi-content">
                <div class="kpi-value"><?php echo $stats['active_visits']; ?></div>
                <div class="kpi-label">Active Visits</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
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
            
            <a href="all_visitors.php" class="action-card">
                <div class="action-icon">🔍</div>
                <div class="action-title">Search Visitor</div>
                <div class="action-desc">Find visitor by name or ID</div>
            </a>
        </div>
    </div>

    </div>

<footer>
    &copy; <?php echo date("Y"); ?> Hospital Visitor System • Dashboard
</footer>

</body>
</html>
