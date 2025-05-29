<?php
// Start session and check login at the VERY TOP
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Add this after session_start() and before the first include
$notification = [];

// Get URL parameters for notifications
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
    $error = isset($_GET['error']) ? $_GET['error'] : '';

    switch($status) {
        case 'failed':
            $notification = [
                'type' => 'error',
                'message' => $error === 'payment_failed' ? 
                    "Payment failed for Order #$order_id. Please try again or contact support." :
                    "An error occurred while processing Order #$order_id."
            ];
            break;
        case 'pending':
            $notification = [
                'type' => 'warning',
                'message' => "Order #$order_id is pending payment confirmation."
            ];
            break;
        case 'processing':
            $notification = [
                'type' => 'info',
                'message' => "Order #$order_id is being processed."
            ];
            break;
        case 'completed':
            $notification = [
                'type' => 'success',
                'message' => "Order #$order_id has been completed successfully!"
            ];
            break;
    }
}

include 'includes/config.php';
require_once 'includes/auth.php';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    // Basic validation
    if (empty($name) || empty($email)) {
        $profile_error = "Name and email are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profile_error = "Invalid email format";
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $profile_success = "Profile updated successfully!";
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
        } else {
            $profile_error = "Error updating profile: " . $conn->error;
        }
    }
}

// Handle password change (now part of profile edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password change
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "All password fields are required";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords don't match";
    } elseif (strlen($new_password) < 8) {
        $password_error = "Password must be at least 8 characters";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $password_success = "Password changed successfully!";
            } else {
                $password_error = "Error changing password: " . $conn->error;
            }
        } else {
            $password_error = "Current password is incorrect";
        }
    }
}

// Get user data
$stmt = $conn->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user orders with product images
$stmt = $conn->prepare("
    WITH UserOrders AS (
        SELECT 
            o.*,
            ROW_NUMBER() OVER (PARTITION BY o.user_id ORDER BY o.created_at DESC) as user_order_number,
            COUNT(*) OVER (PARTITION BY o.user_id) as total_user_orders
        FROM orders o
        WHERE o.user_id = ?
    )
    SELECT 
        uo.*,
        GROUP_CONCAT(p.name) as product_names,
        GROUP_CONCAT(p.image) as product_images,
        GROUP_CONCAT(oi.quantity) as quantities,
        GROUP_CONCAT(oi.price) as item_prices
    FROM UserOrders uo
    JOIN order_items oi ON uo.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    GROUP BY uo.id, uo.user_id, uo.total, uo.status, uo.created_at, uo.shipping_name, 
             uo.shipping_email, uo.shipping_address, uo.shipping_city, uo.shipping_state, 
             uo.shipping_zip, uo.payment_method, uo.tracking_number, uo.updated_at, 
             uo.user_order_number, uo.total_user_orders
    ORDER BY uo.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get highlight order ID from URL if present
$highlight_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
$new_order_status = isset($_GET['status']) ? $_GET['status'] : null;

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);
    
    // Only allow cancellation if order is in 'pending' or 'processing' status
    $cancel_stmt = $conn->prepare("
        UPDATE orders 
        SET status = 'cancelled', 
            updated_at = CURRENT_TIMESTAMP,
            cancellation_date = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ? AND (status = 'pending' OR status = 'processing')
    ");
    $cancel_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    
    if ($cancel_stmt->execute() && $cancel_stmt->affected_rows > 0) {
        // Get order details for notification
        $order_query = $conn->prepare("
            SELECT o.*, p.seller_id, p.name as product_name
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.id = ? LIMIT 1
        ");
        $order_query->bind_param("i", $order_id);
        $order_query->execute();
        $order_result = $order_query->get_result()->fetch_assoc();
        
        if ($order_result) {
            try {
                // Try to insert notification (will fail silently if table doesn't exist)
                $notification_stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, message, reference_id, created_at)
                    VALUES (?, 'order_cancelled', ?, ?, CURRENT_TIMESTAMP)
                ");
                $seller_id = $order_result['seller_id'];
                $message = "Order #" . $order_id . " for " . $order_result['product_name'] . " has been cancelled by the customer.";
                $notification_stmt->bind_param("isi", $seller_id, $message, $order_id);
                $notification_stmt->execute();
            } catch (Exception $e) {
                // Ignore notification errors - the order is still cancelled successfully
            }
        }
        
        $_SESSION['success_msg'] = "Order cancelled successfully.";
    } else {
        $_SESSION['error_msg'] = "Failed to cancel order. Please try again.";
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <style>
    /* Dark Sunset Theme */
    :root {
        --sunset-dark: #1a1a2e;
        --sunset-darker: #16213e;
        --sunset-orange: #ff7b25;
        --sunset-pink: #ff4d6d;
        --sunset-purple: #6a2c70;
        --sunset-red: #e94560;
        --sunset-yellow: #ffd32d;
        --text-light: #f8f9fa;
        --text-muted: #adb5bd;
        --success-color: #28a745;
        --error-color: #dc3545;
    }

    body {
        background: linear-gradient(135deg, var(--sunset-dark), var(--sunset-darker));
        color: var(--text-light);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
        margin: 0;
        padding: 0;
        line-height: 1.6;
    }

    .account-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
        margin-top: calc(2rem + 60px);
    }

    .account-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .account-title {
        font-size: 2.5rem;
        background: linear-gradient(to right, var(--sunset-orange), var(--sunset-pink));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 0.5rem;
    }

    .account-subtitle {
        color: var(--text-muted);
        font-size: 1.1rem;
    }

    .profile-section, .password-section, .orders-section {
        background: rgba(26, 26, 46, 0.8);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 2.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 77, 109, 0.2);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        border-bottom: 1px solid rgba(255, 123, 37, 0.2);
        padding-bottom: 1rem;
    }

    .profile-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .profile-detail {
        margin-bottom: 1.5rem;
    }

    .profile-detail label {
        display: block;
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
    }

    .profile-detail p {
        font-size: 1.1rem;
        margin: 0;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(255, 123, 37, 0.1);
    }

    .btn {
        display: inline-block;
        padding: 0.8rem 1.5rem;
        background: linear-gradient(to right, var(--sunset-orange), var(--sunset-pink));
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid var(--sunset-pink);
        color: var(--sunset-pink);
        transition: all 0.3s ease;
    }

    .btn-outline:hover {
        background: linear-gradient(to right, var(--sunset-orange), var(--sunset-pink));
        color: white;
        border-color: transparent;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 77, 109, 0.4);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-light);
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 0.8rem;
        background: rgba(22, 33, 62, 0.5);
        border: 1px solid rgba(255, 123, 37, 0.3);
        border-radius: 8px;
        color: var(--text-light);
        font-size: 1rem;
        transition: border 0.3s ease;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus {
        outline: none;
        border-color: var(--sunset-pink);
        box-shadow: 0 0 0 2px rgba(255, 77, 109, 0.2);
    }

    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }

    .alert-success {
        background-color: rgba(40, 167, 69, 0.2);
        border: 1px solid rgba(40, 167, 69, 0.3);
        color: var(--success-color);
    }

    .alert-danger {
        background-color: rgba(220, 53, 69, 0.2);
        border: 1px solid rgba(220, 53, 69, 0.3);
        color: var(--error-color);
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .password-toggle {
        position: relative;
    }

    .password-toggle-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--text-muted);
    }

    /* Orders Section */
    .order-card {
        background: rgba(22, 33, 62, 0.5);
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(255, 123, 37, 0.2);
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(255, 123, 37, 0.1);
    }

    .order-number {
        font-weight: bold;
        color: var(--sunset-orange);
    }

    .order-date {
        color: var(--text-muted);
    }

    .order-status {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
    }

    .processing { background: #17a2b8; color: #fff; }
    .pending { background: #ffc107; color: #000; }
    .shipped { background: #007bff; color: #fff; }
    .delivered { background: #28a745; color: #fff; }
    .cancelled { background: #dc3545; color: #fff; }

    .order-total {
        font-weight: bold;
        color: var(--sunset-pink);
    }

    .order-shipping {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 123, 37, 0.1);
    }

    .order-shipping h4 {
        margin-bottom: 0.5rem;
        color: var(--sunset-orange);
    }

    .order-shipping p {
        margin: 0.3rem 0;
    }

    .order-details {
        display: flex;
        flex-direction: column;
        gap: 15px;
        padding: 15px 0;
    }

    .order-items {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .order-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 10px;
        background: rgba(30, 20, 35, 0.4);
        border-radius: 8px;
        border: 1px solid rgba(139, 69, 19, 0.2);
    }

    .item-image {
        width: 60px;
        height: 60px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 8px;
        background: rgba(22, 33, 62, 0.5);
        border: 1px solid rgba(139, 69, 19, 0.3);
    }

    .product-thumbnail {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 6px;
    }

    .no-image {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        font-size: 1.2rem;
    }

    .item-details {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .item-name {
        font-weight: 500;
        color: var(--text-light);
    }

    .item-quantity {
        color: var(--sunset-orange);
        font-weight: 500;
        font-size: 0.9rem;
        padding: 2px 8px;
        background: rgba(255, 123, 37, 0.1);
        border-radius: 4px;
    }

    .order-info {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        padding: 15px;
        background: rgba(30, 20, 35, 0.4);
        border-radius: 8px;
        border: 1px solid rgba(139, 69, 19, 0.2);
    }

    .order-total {
        font-weight: bold;
        color: var(--sunset-pink);
    }

    .order-status {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.9rem;
    }

    .order-status.pending {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
    }

    .order-status.processing {
        background: rgba(13, 202, 240, 0.2);
        color: #0dcaf0;
    }

    .order-status.completed {
        background: rgba(25, 135, 84, 0.2);
        color: #198754;
    }

    .order-status.cancelled {
        background: rgba(220, 53, 69, 0.2);
        color: #dc3545;
    }

    .cod-instructions {
        margin-top: 15px;
        padding: 10px;
        background: rgba(255, 193, 7, 0.1);
        border-radius: 4px;
        color: #ffc107;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .no-orders {
        text-align: center;
        color: rgba(240, 230, 210, 0.7);
        padding: 30px;
    }

    .tab-container {
        display: flex;
        margin-bottom: 2rem;
        border-bottom: 1px solid rgba(255, 123, 37, 0.2);
    }

    .tab {
        padding: 0.8rem 1.5rem;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }

    .tab.active {
        border-bottom-color: var(--sunset-pink);
        color: var(--sunset-pink);
        font-weight: bold;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    @media (max-width: 768px) {
        .profile-content {
            grid-template-columns: 1fr;
        }
        
        .account-container {
            padding: 1.5rem;
        }
        
        .profile-section, .password-section, .orders-section {
            padding: 1.5rem;
        }
        
        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .order-header {
            flex-direction: column;
            gap: 0.5rem;
        }

        .order-item {
            flex-direction: column;
            align-items: flex-start;
        }

        .order-item-image {
            margin-bottom: 0.5rem;
        }

        .item-details {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }

        .order-info {
            flex-direction: column;
        }

        .action-buttons {
            justify-content: stretch;
        }

        .btn-cancel {
            width: 100%;
            justify-content: center;
        }
    }

    .btn-seller {
        background: linear-gradient(to right,rgb(255, 255, 255),rgb(255, 255, 255));
        color: white;
        text-decoration: none;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
        transition: all 0.3s;
    }

    .btn-seller:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(155, 77, 202, 0.3);
        background: linear-gradient(to right, #ff8ba7, #9b4dca);
        color: white;
    }

    .btn-seller i {
        font-size: 1.1rem;
    }

    .btn-cancel {
        background: linear-gradient(to right, #dc3545, #c82333);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: linear-gradient(to right, #c82333, #a51f2d);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }

    .cancel-form {
        display: inline-block;
        margin-top: 10px;
    }

    .order-header {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .order-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .cancel-form {
            margin-left: 0;
            margin-top: 10px;
            width: 100%;
        }
        
        .btn-cancel {
            width: 100%;
            justify-content: center;
        }
    }

    /* Add these styles for the orders tab */
    .orders-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .order-card {
        background: rgba(30, 20, 35, 0.8);
        border: 1px solid #8B4513;
        border-radius: 8px;
        padding: 15px;
        transition: all 0.3s ease;
    }

    .order-card.highlighted {
        border-color: #CD5C5C;
        box-shadow: 0 0 15px rgba(205, 92, 92, 0.3);
        animation: highlight-pulse 2s infinite;
    }

    @keyframes highlight-pulse {
        0% { box-shadow: 0 0 15px rgba(205, 92, 92, 0.3); }
        50% { box-shadow: 0 0 25px rgba(205, 92, 92, 0.5); }
        100% { box-shadow: 0 0 15px rgba(205, 92, 92, 0.3); }
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(139, 69, 19, 0.3);
    }

    .order-id {
        font-size: 1.1rem;
        color: #CD5C5C;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    .order-id span.new-order-badge {
        font-size: 0.8rem;
        background: #CD5C5C;
        color: white;
        padding: 3px 8px;
        border-radius: 12px;
        font-weight: normal;
    }

    .order-date {
        color: rgba(240, 230, 210, 0.7);
        font-size: 0.9rem;
    }

    .order-details {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .order-items {
        color: #f0e6d2;
    }

    .order-info {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 10px;
    }

    .order-status {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.9rem;
    }

    .order-status.pending {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
    }

    .order-status.processing {
        background: rgba(13, 202, 240, 0.2);
        color: #0dcaf0;
    }

    .order-status.completed {
        background: rgba(25, 135, 84, 0.2);
        color: #198754;
    }

    .order-status.cancelled {
        background: rgba(220, 53, 69, 0.2);
        color: #dc3545;
    }

    .cod-instructions {
        margin-top: 15px;
        padding: 10px;
        background: rgba(255, 193, 7, 0.1);
        border-radius: 4px;
        color: #ffc107;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .no-orders {
        text-align: center;
        color: rgba(240, 230, 210, 0.7);
        padding: 30px;
    }

    /* Custom Confirmation Dialog Styles */
    .custom-confirm-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        backdrop-filter: blur(5px);
    }

    .custom-confirm-content {
        background: linear-gradient(135deg, rgba(26, 26, 46, 0.95), rgba(22, 33, 62, 0.95));
        padding: 25px;
        border-radius: 12px;
        width: 90%;
        max-width: 400px;
        border: 1px solid rgba(255, 123, 37, 0.3);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .custom-confirm-content h3 {
        color: var(--sunset-orange);
        margin: 0 0 15px 0;
        font-size: 1.2rem;
        border-bottom: 1px solid rgba(255, 123, 37, 0.2);
        padding-bottom: 10px;
    }

    .custom-confirm-content p {
        color: var(--text-light);
        margin: 0 0 20px 0;
        font-size: 1rem;
        line-height: 1.5;
    }

    .custom-confirm-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .custom-confirm-button {
        padding: 8px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .confirm-yes {
        background: linear-gradient(to right, var(--sunset-orange), var(--sunset-pink));
        color: white;
    }

    .confirm-no {
        background: rgba(30, 20, 35, 0.8);
        color: var(--text-light);
        border: 1px solid rgba(255, 123, 37, 0.3);
    }

    .custom-confirm-button:hover {
        transform: translateY(-2px);
    }

    .confirm-yes:hover {
        box-shadow: 0 4px 15px rgba(255, 123, 37, 0.3);
    }

    .confirm-no:hover {
        background: rgba(40, 30, 45, 0.8);
        border-color: var(--sunset-orange);
    }

    @media (max-width: 480px) {
        .custom-confirm-content {
            width: 95%;
            padding: 20px;
        }

        .custom-confirm-buttons {
            flex-direction: column;
        }

        .custom-confirm-button {
            width: 100%;
            padding: 12px;
            text-align: center;
        }
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        backdrop-filter: blur(5px);
    }

    .modal-content {
        position: relative;
        background: linear-gradient(135deg, rgba(26, 26, 46, 0.95), rgba(22, 33, 62, 0.95));
        margin: 5% auto;
        padding: 25px;
        border-radius: 12px;
        width: 90%;
        max-width: 800px;
        border: 1px solid rgba(255, 123, 37, 0.3);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        color: #f0e6d2;
        max-height: 90vh;
        overflow-y: auto;
    }

    .close-modal {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 24px;
        cursor: pointer;
        color: rgba(240, 230, 210, 0.7);
        transition: all 0.3s ease;
    }

    .close-modal:hover {
        color: #CD5C5C;
    }

    .order-details-content {
        margin-top: 20px;
    }

    .order-info-section {
        background: rgba(30, 20, 35, 0.4);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .order-header-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .order-number {
        font-size: 1.2rem;
        color: #CD5C5C;
        font-weight: 500;
    }

    .order-status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .order-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .meta-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .meta-label {
        color: rgba(240, 230, 210, 0.7);
        font-size: 0.9rem;
    }

    .meta-value {
        color: #f0e6d2;
        font-weight: 500;
    }

    .shipping-details, .items-list {
        background: rgba(30, 20, 35, 0.4);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .shipping-details h3, .items-list h3 {
        color: #CD5C5C;
        margin-bottom: 15px;
        font-size: 1.1rem;
    }

    .shipping-content {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .order-items-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .order-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 10px;
        background: rgba(22, 33, 62, 0.5);
        border-radius: 6px;
    }

    .item-image {
        width: 60px;
        height: 60px;
        border-radius: 6px;
        overflow: hidden;
    }

    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .item-details {
        flex: 1;
    }

    .item-name {
        font-weight: 500;
        margin-bottom: 5px;
    }

    .item-quantity {
        color: rgba(240, 230, 210, 0.7);
        font-size: 0.9rem;
    }

    .verification-info {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
        padding: 12px;
        border-radius: 6px;
        margin-top: 20px;
    }

    .verification-info i {
        font-size: 1.2rem;
    }

    /* Make order cards clickable */
    .order-card {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(205, 92, 92, 0.2);
    }

    @media (max-width: 768px) {
        .modal-content {
            margin: 10% auto;
            padding: 20px;
            width: 95%;
        }

        .order-meta {
            grid-template-columns: 1fr;
        }
    }

    .notification-banner {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        padding: 1rem;
        z-index: 1001;
        animation: slideDown 0.5s ease-out;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    @keyframes slideDown {
        from {
            transform: translateY(-100%);
        }
        to {
            transform: translateY(0);
        }
    }

    .notification-banner.error {
        background: rgba(220, 53, 69, 0.95);
        color: white;
    }

    .notification-banner.warning {
        background: rgba(255, 193, 7, 0.95);
        color: #000;
    }

    .notification-banner.success {
        background: rgba(40, 167, 69, 0.95);
        color: white;
    }

    .notification-banner.info {
        background: rgba(23, 162, 184, 0.95);
        color: white;
    }

    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        max-width: 800px;
        margin: 0 auto;
        font-weight: 500;
    }

    .notification-content i {
        font-size: 1.2rem;
    }

    .notification-close {
        background: none;
        border: none;
        color: inherit;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0 0.5rem;
        opacity: 0.8;
        transition: opacity 0.3s;
    }

    .notification-close:hover {
        opacity: 1;
    }
    </style>
</head>
<body>
    <?php if (!empty($notification)): ?>
        <div class="notification-banner <?= $notification['type'] ?>">
            <div class="notification-content">
                <i class="fas <?= $notification['type'] === 'error' ? 'fa-exclamation-circle' : 
                               ($notification['type'] === 'warning' ? 'fa-exclamation-triangle' : 
                               ($notification['type'] === 'success' ? 'fa-check-circle' : 'fa-info-circle')) ?>">
                </i>
                <span><?= htmlspecialchars($notification['message']) ?></span>
            </div>
            <button class="notification-close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
    <?php endif; ?>

    <?php include 'includes/header.php'; ?>

    <div class="account-container">
        <div class="account-header">
            <h1 class="account-title">My Account Dashboard
            </h1>
        </div>

        <div class="tab-container">
            <div class="tab active" onclick="switchTab('profile')">Profile</div>
            <div class="tab" onclick="switchTab('orders')">My Orders</div>
        </div>

        <div id="profileTab" class="tab-content active">
            <div class="profile-section">
                <div class="section-header">
                    <h2>Profile Information</h2>
                    <button id="editToggle" class="btn btn-outline">Edit Profile</button>
                </div>
                
                <?php if (isset($profile_success)): ?>
                    <div class="alert alert-success"><?= $profile_success ?></div>
                <?php endif; ?>
                <?php if (isset($profile_error)): ?>
                    <div class="alert alert-danger"><?= $profile_error ?></div>
                <?php endif; ?>
                
                <form id="profileForm" method="POST" style="display: none;">
                    <div class="profile-content">
                        <div>
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        <div>
                            <div class="profile-detail">
                                <label>Member Since</label>
                                <p><?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                            </div>
                            <div class="profile-detail">
                                <label>Account Status</label>
                                <p>Active</p>
                            </div>
                        </div>
                    </div>

                    <!-- Password Change Section (only visible when editing) -->
                    <div class="password-section" style="margin-top: 2rem;">
                        <div class="section-header">
                            <h2>Change Password</h2>
                        </div>
                        
                        <?php if (isset($password_success)): ?>
                            <div class="alert alert-success"><?= $password_success ?></div>
                        <?php endif; ?>
                        <?php if (isset($password_error)): ?>
                            <div class="alert alert-danger"><?= $password_error ?></div>
                        <?php endif; ?>
                        
                        <div class="form-group password-toggle">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password">
                            <span class="password-toggle-icon" onclick="togglePassword('current_password')">👁️</span>
                        </div>
                        
                        <div class="form-group password-toggle">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password">
                            <span class="password-toggle-icon" onclick="togglePassword('new_password')">👁️</span>
                        </div>
                        
                        <div class="form-group password-toggle">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                            <span class="password-toggle-icon" onclick="togglePassword('confirm_password')">👁️</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn">Save Changes</button>
                        <button type="button" id="cancelEdit" class="btn btn-outline">Cancel</button>
                    </div>
                </form>
                
                <div id="profileView">
                    <div class="profile-content">
                        <div>
                            <div class="profile-detail">
                                <label>Full Name</label>
                                <p><?= htmlspecialchars($user['name']) ?></p>
                            </div>
                            <div class="profile-detail">
                                <label>Email Address</label>
                                <p><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                            <?php if (!isSeller()): ?>
                            <div class="profile-detail">
                                <a href="become_seller.php" class="btn btn-seller">
                                    <i class="fas fa-store"></i> Become a Seller
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="profile-detail">
                                <a href="seller/dashboard.php" class="btn btn-seller">
                                    <i class="fas fa-store"></i> Go to Seller Dashboard
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="profile-detail">
                                <label>Member Since</label>
                                <p><?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                            </div>
                            <div class="profile-detail">
                                <label>Account Status</label>
                                <p>Active</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="ordersTab" class="tab-content">
            <div class="orders-section">
                <div class="section-header">
                    <h2>Order History</h2>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="no-orders">
                        <p>You haven't placed any orders yet.</p>
                        <a href="shop.php" class="btn">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): 
                            $is_highlighted = $highlight_order_id && $order['id'] == $highlight_order_id;
                            $order_class = $is_highlighted ? 'order-card highlighted' : 'order-card';
                        ?>
                            <div class="<?= $order_class ?>" onclick="showOrderDetails(<?= htmlspecialchars(json_encode($order)) ?>)">
                                <div class="order-header">
                                    <div class="order-id">
                                        Order <?= $order['user_order_number'] ?> of <?= $order['total_user_orders'] ?>
                                        <?php if ($is_highlighted && $new_order_status === 'pending'): ?>
                                            <span class="new-order-badge">New Order</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="order-date"><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                                </div>
                                
                                <div class="order-details">
                                    <div class="order-items">
                                        <?php 
                                        $product_names = explode(',', $order['product_names']);
                                        $product_images = explode(',', $order['product_images']);
                                        $quantities = explode(',', $order['quantities']);
                                        
                                        foreach ($product_names as $i => $name): 
                                            $image = isset($product_images[$i]) ? trim($product_images[$i]) : '';
                                            $quantity = isset($quantities[$i]) ? trim($quantities[$i]) : 0;
                                        ?>
                                            <div class="order-item">
                                                <div class="item-image">
                                                    <?php if ($image && file_exists("uploads/products/" . $image)): ?>
                                                        <img src="uploads/products/<?= htmlspecialchars($image) ?>" 
                                                             alt="<?= htmlspecialchars($name) ?>"
                                                             class="product-thumbnail">
                                                    <?php else: ?>
                                                        <div class="no-image">
                                                            <i class="fas fa-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="item-details">
                                                    <span class="item-name"><?= htmlspecialchars($name) ?></span>
                                                    <span class="item-quantity">×<?= $quantity ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="order-info">
                                        <div class="order-total">
                                            <strong>Total:</strong> ₱<?= number_format($order['total'], 2) ?>
                                        </div>
                                        <div class="order-status <?= strtolower($order['status']) ?>">
                                            <strong>Status:</strong> <?= ucfirst($order['status']) ?>
                                        </div>
                                        <div class="payment-method">
                                            <strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']) ?>
                                        </div>
                                    </div>
                                    <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                                        <div class="action-buttons">
                                            <form method="POST" class="cancel-form" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <button type="submit" name="cancel_order" class="btn-cancel">
                                                    <i class="fas fa-times"></i> Cancel Order
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($order['payment_method'] === 'Cash on Delivery' && $order['status'] === 'pending'): ?>
                                    <div class="cod-instructions">
                                        <i class="fas fa-info-circle"></i>
                                        Please prepare ₱<?= number_format($order['total'], 2) ?> upon delivery.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Order Details</h2>
            <div class="order-details-content">
                <div class="order-info-section">
                    <div class="order-header-details">
                        <div class="order-number"></div>
                        <div class="order-status-badge"></div>
                    </div>
                    <div class="order-meta">
                        <div class="meta-item">
                            <span class="meta-label">Order Date</span>
                            <span class="meta-value" id="orderDate"></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Payment Method</span>
                            <span class="meta-value" id="paymentMethod"></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Total Amount</span>
                            <span class="meta-value" id="orderTotal"></span>
                        </div>
                    </div>
                </div>
                <div class="shipping-details">
                    <h3>Shipping Information</h3>
                    <div class="shipping-content">
                        <div class="shipping-name"></div>
                        <div class="shipping-address"></div>
                        <div class="shipping-contact"></div>
                    </div>
                </div>
                <div class="items-list">
                    <h3>Order Items</h3>
                    <div class="order-items-container"></div>
                </div>
                <?php if ($order['status'] === 'completed'): ?>
                    <div class="verification-info">
                        <i class="fas fa-shield-alt"></i>
                        Payment verified at <span class="verification-time"></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Toggle profile edit form
    const editToggle = document.getElementById('editToggle');
    const cancelEdit = document.getElementById('cancelEdit');
    const profileForm = document.getElementById('profileForm');
    const profileView = document.getElementById('profileView');
    
    editToggle.addEventListener('click', () => {
        profileForm.style.display = 'block';
        profileView.style.display = 'none';
        editToggle.style.display = 'none';
    });
    
    cancelEdit.addEventListener('click', () => {
        profileForm.style.display = 'none';
        profileView.style.display = 'block';
        editToggle.style.display = 'block';
    });

    // Custom confirmation dialog
    function customConfirm(message) {
        return new Promise((resolve) => {
            // Create modal container
            const modal = document.createElement('div');
            modal.className = 'custom-confirm-modal';
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.className = 'custom-confirm-content';
            
            // Add title
            const title = document.createElement('h3');
            title.textContent = 'Confirm action on localhost';
            modalContent.appendChild(title);
            
            // Add message
            const messageEl = document.createElement('p');
            messageEl.textContent = message;
            modalContent.appendChild(messageEl);
            
            // Add buttons container
            const buttons = document.createElement('div');
            buttons.className = 'custom-confirm-buttons';
            
            // Add Yes button
            const yesButton = document.createElement('button');
            yesButton.textContent = 'Yes';
            yesButton.className = 'custom-confirm-button confirm-yes';
            yesButton.onclick = () => {
                document.body.removeChild(modal);
                resolve(true);
            };
            
            // Add No button
            const noButton = document.createElement('button');
            noButton.textContent = 'No';
            noButton.className = 'custom-confirm-button confirm-no';
            noButton.onclick = () => {
                document.body.removeChild(modal);
                resolve(false);
            };
            
            // Add buttons to container
            buttons.appendChild(yesButton);
            buttons.appendChild(noButton);
            modalContent.appendChild(buttons);
            
            // Add content to modal
            modal.appendChild(modalContent);
            
            // Add modal to body
            document.body.appendChild(modal);
            
            // Focus No button by default (safer option)
            noButton.focus();
        });
    }

    // Update the cancel form submission
    document.querySelectorAll('.cancel-form').forEach(form => {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const confirmed = await customConfirm('Are you sure you want to cancel this order?');
            if (confirmed) {
                e.target.submit();
            }
        };
    });

    // Toggle password visibility
    function togglePassword(id) {
        const input = document.getElementById(id);
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }

    // Tab switching
    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Deactivate all tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Activate selected tab
        document.getElementById(tabName + 'Tab').classList.add('active');
        event.currentTarget.classList.add('active');
    }

    function showOrderDetails(order) {
        const modal = document.getElementById('orderDetailsModal');
        const modalContent = modal.querySelector('.modal-content');

        // Set order number and status
        modalContent.querySelector('.order-number').textContent = `Order #${order.id}`;
        
        // Set status with appropriate styling
        const statusBadge = modalContent.querySelector('.order-status-badge');
        statusBadge.textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
        statusBadge.className = 'order-status-badge ' + order.status.toLowerCase();

        // Set order meta information
        document.getElementById('orderDate').textContent = new Date(order.created_at).toLocaleString();
        document.getElementById('paymentMethod').textContent = order.payment_method;
        document.getElementById('orderTotal').textContent = '₱' + parseFloat(order.total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

        // Set shipping information
        const shippingContent = modalContent.querySelector('.shipping-content');
        shippingContent.innerHTML = `
            <div class="shipping-name">${order.shipping_name}</div>
            <div class="shipping-address">
                ${order.shipping_address}<br>
                ${order.shipping_city}, ${order.shipping_state} ${order.shipping_zip}
            </div>
            <div class="shipping-contact">${order.shipping_email}</div>
        `;

        // Set order items
        const itemsContainer = modalContent.querySelector('.order-items-container');
        const productNames = order.product_names.split(',');
        const quantities = order.quantities.split(',');
        
        let itemsHTML = '';
        productNames.forEach((name, index) => {
            itemsHTML += `
                <div class="order-item">
                    <div class="item-details">
                        <div class="item-name">${name}</div>
                        <div class="item-quantity">Quantity: ${quantities[index]}</div>
                    </div>
                </div>
            `;
        });
        itemsContainer.innerHTML = itemsHTML;

        // Show verification info if payment is completed
        const verificationInfo = modalContent.querySelector('.verification-info');
        if (verificationInfo && order.payment_verified_at) {
            verificationInfo.querySelector('.verification-time').textContent = 
                new Date(order.payment_verified_at).toLocaleTimeString();
            verificationInfo.style.display = 'flex';
        } else if (verificationInfo) {
            verificationInfo.style.display = 'none';
        }

        // Show modal
        modal.style.display = 'block';

        // Close modal when clicking the X or outside the modal
        const closeBtn = modal.querySelector('.close-modal');
        closeBtn.onclick = () => modal.style.display = 'none';
        
        window.onclick = (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    }
    </script>
</body>
</html>