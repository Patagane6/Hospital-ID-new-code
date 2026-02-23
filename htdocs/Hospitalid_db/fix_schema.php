<?php
/**
 * Simple Database Schema Fix Script
 * Fixes visitor table columns without touching foreign keys
 */

require_once __DIR__ . '/Includes/database.php';

if (!$conn) {
    die("Database connection failed. Please check your database configuration.");
}

echo "<h2>Database Schema Fix</h2>";
echo "<p>Fixing visitor table columns...</p>";

$updates = [];
$errors = [];

// Get current table structure
$sql = "DESCRIBE visitor";
$result = $conn->query($sql);
$current_structure = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $current_structure[$row['Field']] = $row;
    }
}

echo "<h3>Current Structure:</h3><pre>";
print_r($current_structure);
echo "</pre>";

// Fix 1: Ensure visitor_id has AUTO_INCREMENT
if (isset($current_structure['visitor_id'])) {
    $extra = $current_structure['visitor_id']['Extra'] ?? '';
    if (strpos($extra, 'auto_increment') === false) {
        $sql = "ALTER TABLE `visitor` MODIFY COLUMN `visitor_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
        if ($conn->query($sql) === TRUE) {
            $updates[] = "✅ Fixed visitor_id to AUTO_INCREMENT";
        } else {
            $errors[] = "❌ Error fixing visitor_id: " . $conn->error;
        }
    } else {
        $updates[] = "ℹ️ visitor_id already has AUTO_INCREMENT";
    }
}

// Fix 2: Change contact_number to VARCHAR(15) if it's not already
if (isset($current_structure['contact_number'])) {
    $type = strtoupper($current_structure['contact_number']['Type']);
    if (strpos($type, 'VARCHAR') === false || strpos($type, '(15)') === false) {
        $sql = "ALTER TABLE `visitor` MODIFY COLUMN `contact_number` VARCHAR(15) NOT NULL";
        if ($conn->query($sql) === TRUE) {
            $updates[] = "✅ Changed contact_number to VARCHAR(15)";
        } else {
            $errors[] = "❌ Error changing contact_number: " . $conn->error;
        }
    } else {
        $updates[] = "ℹ️ contact_number already VARCHAR(15)";
    }
}

// Fix 3: Ensure full_name is VARCHAR(100)
if (isset($current_structure['full_name'])) {
    $type = strtoupper($current_structure['full_name']['Type']);
    if (strpos($type, 'VARCHAR(100)') === false) {
        $sql = "ALTER TABLE `visitor` MODIFY COLUMN `full_name` VARCHAR(100) NOT NULL";
        if ($conn->query($sql) === TRUE) {
            $updates[] = "✅ Changed full_name to VARCHAR(100)";
        } else {
            $errors[] = "❌ Error changing full_name: " . $conn->error;
        }
    } else {
        $updates[] = "ℹ️ full_name already VARCHAR(100)";
    }
}

// Fix 4: Ensure number_of_visitors is INT
if (isset($current_structure['number_of_visitors'])) {
    $type = strtoupper($current_structure['number_of_visitors']['Type']);
    if (strpos($type, 'INT') === false) {
        $sql = "ALTER TABLE `visitor` MODIFY COLUMN `number_of_visitors` INT(11) NOT NULL DEFAULT 1";
        if ($conn->query($sql) === TRUE) {
            $updates[] = "✅ Changed number_of_visitors to INT(11)";
        } else {
            $errors[] = "❌ Error changing number_of_visitors: " . $conn->error;
        }
    } else {
        $updates[] = "ℹ️ number_of_visitors already INT";
    }
}

// Fix 5: Add created_at if missing
if (!isset($current_structure['created_at'])) {
    $sql = "ALTER TABLE `visitor` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
    if ($conn->query($sql) === TRUE) {
        $updates[] = "✅ Added created_at column";
    } else {
        $errors[] = "❌ Error adding created_at: " . $conn->error;
    }
} else {
    $updates[] = "ℹ️ created_at column already exists";
}

// Display results
echo "<h3>Update Results:</h3>";
echo "<ul>";
foreach ($updates as $update) {
    echo "<li style='color: green; padding: 8px; background: #d4edda; margin: 4px 0; border-radius: 4px;'>$update</li>";
}
foreach ($errors as $error) {
    echo "<li style='color: red; padding: 8px; background: #f8d7da; margin: 4px 0; border-radius: 4px;'>$error</li>";
}
echo "</ul>";

if (empty($errors)) {
    echo "<p style='color: green; font-weight: bold; font-size: 1.2em;'>✅ All fixes applied successfully!</p>";
    echo "<p><a href='visitor.php' style='padding: 10px 20px; background: #6fa39e; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>Go to Visitor Management →</a></p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Some errors occurred. Please check the messages above.</p>";
    echo "<p>You may need to manually fix these issues in phpMyAdmin.</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Schema Fix</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <style>
        body { padding: 40px; font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; }
        h2 { color: #2f5553; }
        ul { list-style-type: none; padding: 0; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto; }
    </style>
</body>
</html>
