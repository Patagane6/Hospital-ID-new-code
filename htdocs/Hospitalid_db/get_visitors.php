<?php
require_once __DIR__ . '/Includes/database.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

$visitors = [];

if ($conn) {
    $result = $conn->query("SELECT visitor_id, full_name, contact_number, created_at FROM visitor ORDER BY created_at DESC");
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Convert created_at to Philippines timezone
            $dt = new DateTime($row['created_at']);
            $dt->setTimezone(new DateTimeZone('Asia/Manila'));
            $created_display = $dt->format('n/j/Y') . ' ' . $dt->format('g:i A');
            
            $visitors[] = [
                'id' => $row['visitor_id'],
                'name' => $row['full_name'],
                'contact' => $row['contact_number'],
                'dateTime' => $created_display
            ];
        }
    }
    $conn->close();
}

echo json_encode($visitors);
?>
