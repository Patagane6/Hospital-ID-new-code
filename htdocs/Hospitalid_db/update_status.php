<?php
require_once __DIR__ . '/Includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$visitor_id = isset($_POST['visitor_id']) ? intval($_POST['visitor_id']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($visitor_id <= 0 || !in_array($status, ['active', 'inactive'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

if ($conn) {
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    
    // If marking as inactive, set the inactive_at timestamp
    $inactive_at = null;
    if ($status === 'inactive') {
        // Get current time in Philippines timezone and convert to UTC for storage
        $ph_time = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $utc_time = clone $ph_time;
        $utc_time->setTimezone(new DateTimeZone('UTC'));
        $inactive_at = $utc_time->format('Y-m-d H:i:s');
    }
    
    $stmt = $conn->prepare("UPDATE visitor SET status = ?, inactive_at = ? WHERE visitor_id = ?");
    $stmt->bind_param("ssi", $status, $inactive_at, $visitor_id);
    
    if ($stmt->execute()) {
        // Get the formatted inactive_at timestamp if it was set
        $inactive_at_display = '';
        if ($status === 'inactive') {
            $ph_time = new DateTime('now', new DateTimeZone('Asia/Manila'));
            $inactive_at_display = $ph_time->format('n/j/Y') . '<br>' . $ph_time->format('g:i A');
        }
        echo json_encode(['success' => true, 'message' => 'Status updated successfully', 'inactive_at_display' => $inactive_at_display]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
}
?>
