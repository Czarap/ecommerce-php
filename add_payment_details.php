<?php
require_once 'includes/config.php';

try {
    // Check if payment_details column exists
    $result = $conn->query("
        SELECT COUNT(*) as column_exists 
        FROM information_schema.columns 
        WHERE table_name = 'orders' 
        AND column_name = 'payment_details'
    ");
    
    $column_exists = $result->fetch_assoc()['column_exists'] > 0;
    
    if (!$column_exists) {
        // Add payment_details column
        $sql = "ALTER TABLE orders ADD COLUMN payment_details JSON AFTER payment_method";
        
        if ($conn->query($sql)) {
            echo "Successfully added payment_details column to orders table<br>";
            echo "<a href='checkout.php'>Return to Checkout</a>";
        } else {
            echo "Error adding column: " . $conn->error;
        }
    } else {
        echo "payment_details column already exists<br>";
        echo "<a href='checkout.php'>Return to Checkout</a>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<a href='checkout.php'>Return to Checkout</a>";
} 