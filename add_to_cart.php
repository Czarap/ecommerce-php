<?php
session_start();
include 'includes/config.php';

header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug information
$debug = [
    'session' => isset($_SESSION['user_id']) ? 'yes' : 'no',
    'post_data' => $_POST,
    'request_method' => $_SERVER['REQUEST_METHOD']
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Please login first',
        'debug' => $debug
    ]);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request method',
        'debug' => $debug
    ]);
    exit();
}

// Validate product_id
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid product ID',
        'debug' => $debug,
        'received_id' => isset($_POST['product_id']) ? $_POST['product_id'] : 'not set'
    ]);
    exit();
}

$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

try {
    // Verify product exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result->fetch_assoc()) {
        throw new Exception("Product not found in database");
    }

    // Check if already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM user_carts WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        // Update quantity
        $new_quantity = $existing['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE user_carts SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $existing['id']);
    } else {
        // Add new item
        $stmt = $conn->prepare("INSERT INTO user_carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $_SESSION['user_id'], $product_id, $quantity);
    }

    $stmt->execute();

    // Get updated cart count
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM user_carts WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;

    $_SESSION['cart_count'] = $count;

    echo json_encode([
        'status' => 'success',
        'cart_count' => $count,
        'message' => 'Product added to cart',
        'debug' => $debug
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => $debug,
        'error_details' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>