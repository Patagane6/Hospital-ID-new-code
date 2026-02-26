<?php
require_once __DIR__ . '/Includes/database.php';

if ($conn) {
    // Check if status column exists
    $result = $conn->query("SHOW COLUMNS FROM visitor LIKE 'status'");
    
    if ($result->num_rows === 0) {
        // Add status column if it doesn't exist
        $conn->query("ALTER TABLE visitor ADD COLUMN status VARCHAR(10) DEFAULT 'active' AFTER created_at");
        echo "Status column added successfully!<br>";
    } else {
        echo "Status column already exists.<br>";
    }
    
    $conn->close();
}

// Redirect back to visitor page
header('Location: visitor.php');
exit;
?>
