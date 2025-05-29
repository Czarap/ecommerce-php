<?php
require_once '../includes/config.php';

// First, backup existing categories
$categories = [];
$result = $conn->query("SELECT * FROM categories");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS=0");

// Drop the table if it exists
$conn->query("DROP TABLE IF EXISTS categories");

// Create the table with correct structure
$sql = "CREATE TABLE `categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1";

if (!$conn->query($sql)) {
    die("Error creating table: " . $conn->error);
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=1");

// Reinsert the backed up categories
if (!empty($categories)) {
    foreach ($categories as $category) {
        $status = $category['status'] ?? 'active';
        $name = $category['name'];
        $icon = $category['icon'];
        
        // Use direct query to preserve IDs
        $sql = "INSERT INTO categories (name, icon, status) VALUES ('$name', '$icon', '$status')";
        $conn->query($sql);
    }
}

// Reset the AUTO_INCREMENT value
$result = $conn->query("SELECT MAX(id) as max_id FROM categories");
$row = $result->fetch_assoc();
$next_id = ($row['max_id'] ?? 0) + 1;
$conn->query("ALTER TABLE categories AUTO_INCREMENT = $next_id");

echo "Categories table has been reset successfully. <a href='admin_categories.php'>Go back to categories</a>";
?> 