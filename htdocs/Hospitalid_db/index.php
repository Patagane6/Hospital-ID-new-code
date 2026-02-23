<?php require_once __DIR__ . '/Includes/database.php'; 

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

$result = $conn->query("SELECT COUNT(*) as total FROM visit_log");
if ($result) {
    $stats['total_visits'] = $result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT COUNT(*) as total FROM visit_log WHERE check_in_time IS NOT NULL AND check_out_time IS NULL");
if ($result) {
    $stats['active_visits'] = $result->fetch_assoc()['total'];
}

// Get recent visitors
$recent_visitors = [];
$result = $conn->query("SELECT * FROM visitor ORDER BY visitor_id DESC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_visitors[] = $row;
    }
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
            <a href="visitor.php" class="nav-link">Visitors</a>
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
        
        <div class="kpi-card">
            <div class="kpi-icon">📊</div>
            <div class="kpi-content">
                <div class="kpi-value"><?php echo $stats['total_visits']; ?></div>
                <div class="kpi-label">Total Visits</div>
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
            
            <a href="visitor.php#list" class="action-card">
                <div class="action-icon">📋</div>
                <div class="action-title">View All Visitors</div>
                <div class="action-desc">Browse visitor records</div>
            </a>
            
            <a href="visitor.php" class="action-card">
                <div class="action-icon">🔍</div>
                <div class="action-title">Search Visitor</div>
                <div class="action-desc">Find visitor by name or ID</div>
            </a>
            
            <a href="visitor.php" class="action-card">
                <div class="action-icon">📈</div>
                <div class="action-title">View Reports</div>
                <div class="action-desc">Generate visit reports</div>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h2>Recent Visitors</h2>
        <?php if (empty($recent_visitors)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p>No visitors registered yet</p>
                <a href="visitor.php" class="btn">Add First Visitor</a>
            </div>
        <?php else: ?>
            <div class="activity-list">
                <?php foreach ($recent_visitors as $visitor): ?>
                    <div class="activity-item">
                        <div class="activity-avatar">👤</div>
                        <div class="activity-details">
                            <div class="activity-name"><?php echo htmlspecialchars($visitor['full_name']); ?></div>
                            <div class="activity-meta">
                                <?php echo htmlspecialchars($visitor['valid_id']); ?> • 
                                <?php echo htmlspecialchars($visitor['contact_number']); ?>
                            </div>
                        </div>
                        <div class="activity-badge">
                            <?php echo htmlspecialchars($visitor['number_of_visitors']); ?> visitor(s)
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="view-all">
                <a href="visitor.php" class="btn secondary">View All Visitors →</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Hospital Visitor System • Dashboard
</footer>

</body>
</html>
