<?php
include 'includes/config.php';

// Get all categories with their status
$result = $conn->query("SELECT id, name, status FROM categories ORDER BY id");

echo "<h2>Categories with their status:</h2>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Status: " . $row['status'] . "\n";
}
echo "</pre>";
?> 