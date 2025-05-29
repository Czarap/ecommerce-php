<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied");
}

try {
    // Check if payment_details column exists
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_details'");
    
    if ($result->num_rows === 0) {
        // Add payment_details column
        $sql = "ALTER TABLE orders ADD COLUMN payment_details JSON AFTER payment_method";
        
        if ($conn->query($sql)) {
            echo "Successfully added payment_details column to orders table";
        } else {
            echo "Error adding column: " . $conn->error;
        }
    } else {
        echo "payment_details column already exists";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br><br><a href='admin_orders.php'>Return to Orders</a>"; 