<?php
session_start();
include '../includes/config.php';
include '../includes/auth.php';

// Check if user is logged in and is a seller
if (!isLoggedIn() || !isSeller()) {
    $response = array('status' => 'error', 'message' => 'Unauthorized access');
    echo json_encode($response);
    exit();
}

// Check if product ID is provided
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    $response = array('status' => 'error', 'message' => 'Product ID is required');
    echo json_encode($response);
    exit();
}

$product_id = intval($_POST['product_id']);
$seller_id = $_SESSION['user_id'];

try {
    // First, verify that the product belongs to this seller
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $product_id, $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Product not found or you do not have permission to delete it');
    }

    // Get the image filename before deletion
    $product = $result->fetch_assoc();
    $image_filename = $product['image'];

    // Start transaction
    $conn->begin_transaction();

    // Delete from user_carts first (foreign key relationship)
    $stmt = $conn->prepare("DELETE FROM user_carts WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    // Delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $product_id, $seller_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to delete product');
    }

    // Commit transaction
    $conn->commit();

    // Delete the product image if it exists
    if ($image_filename && file_exists("../uploads/products/" . $image_filename)) {
        unlink("../uploads/products/" . $image_filename);
    }

    $response = array('status' => 'success', 'message' => 'Product deleted successfully');
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->connect_error === false) {
        $conn->rollback();
    }
    
    $response = array('status' => 'error', 'message' => $e->getMessage());
    echo json_encode($response);
}

// Close connection
$conn->close();
?> 