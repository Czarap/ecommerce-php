<?php
session_start();
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get payment details from URL
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Verify order belongs to user
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: account.php?tab=orders&error=invalid_order");
    exit();
}

// Process payment based on method
$payment_status = 'processing'; // Default status
$error_message = '';

try {
    switch($payment_method) {
        case 'gcash':
            // Here you would integrate with GCash API
            // For demonstration, we'll simulate processing
            $payment_status = simulatePaymentProcessing();
            break;
            
        case 'credit_card':
            // Here you would integrate with Credit Card payment gateway
            // For demonstration, we'll simulate processing
            $payment_status = simulatePaymentProcessing();
            break;
            
        default:
            throw new Exception("Invalid payment method");
    }

    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_verified_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("si", $payment_status, $order_id);
    $stmt->execute();

    // Redirect based on payment status
    if ($payment_status === 'completed') {
        header("Location: order_completed.php?order_id=" . $order_id);
    } else {
        $redirect_url = "account.php?tab=orders&order_id=" . $order_id . "&status=" . $payment_status;
        if ($payment_status === 'failed') {
            $redirect_url .= "&error=payment_failed";
        }
        header("Location: " . $redirect_url);
    }
    exit();

} catch (Exception $e) {
    $error_message = $e->getMessage();
    header("Location: account.php?tab=orders&order_id=" . $order_id . "&status=failed&error=" . urlencode($error_message));
    exit();
}

// Function to simulate payment processing
function simulatePaymentProcessing() {
    // Simulate API call delay
    sleep(1);
    
    // Simulate success rate (90% success, 10% failure)
    return (rand(1, 100) <= 90) ? 'completed' : 'failed';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment</title>
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: #f0e6d2;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .processing-container {
            text-align: center;
            padding: 2rem;
            background: rgba(30, 20, 35, 0.8);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 123, 37, 0.3);
            max-width: 400px;
            width: 90%;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 123, 37, 0.3);
            border-radius: 50%;
            border-top-color: #CD5C5C;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        h2 {
            color: #CD5C5C;
            margin-bottom: 1rem;
        }

        p {
            color: rgba(240, 230, 210, 0.7);
            margin-bottom: 0.5rem;
        }

        .payment-method {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(255, 123, 37, 0.1);
            border-radius: 4px;
            color: #CD5C5C;
            font-weight: 500;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <div class="processing-container">
        <div class="spinner"></div>
        <h2>Processing Payment</h2>
        <p>Please wait while we process your payment...</p>
        <div class="payment-method">
            <?= ucfirst($payment_method) ?>
        </div>
        <p>Order #<?= $order_id ?></p>
    </div>

    <script>
        // Redirect after processing
        setTimeout(() => {
            window.location.href = 'account.php?tab=orders&order_id=<?= $order_id ?>&status=processing';
        }, 3000);
    </script>
</body>
</html> 