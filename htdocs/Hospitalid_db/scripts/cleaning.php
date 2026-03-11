<?php
// Script to delete all visitor rows except the one with visitor_id = 1.
// Run: php scripts/cleanup_visitors.php

require_once __DIR__ . '/../Includes/database.php';

if (!$conn) {
    die("No database connection\n");
}

$result = mysqli_query($conn, "DELETE FROM visitor WHERE visitor_id <> 1");
if (!$result) {
    die('Error: ' . mysqli_error($conn) . "\n");
}

echo "Deleted rows: " . mysqli_affected_rows($conn) . "\n";
