<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

require_once __DIR__ . '/includes/config.php';

$product_id = intval($_POST['product_id'] ?? 0);

if ($product_id < 1) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product']);
    exit();
}

try {
    // Remove item from cart
    $stmt = $conn->prepare("
        DELETE FROM user_carts 
        WHERE user_id = ? AND product_id = ?
    ");
    $stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
    
    if ($stmt->execute()) {
        // Get updated cart count
        $stmt = $conn->prepare("
            SELECT SUM(quantity) as count 
            FROM user_carts 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'] ?? 0;
        
        // Update session cart count
        $_SESSION['cart_count'] = $count;
        
        echo json_encode([
            'status' => 'success',
            'cart_count' => $count
        ]);
    } else {
        throw new Exception("Removal failed");
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>