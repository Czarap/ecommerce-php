<?php
session_start();

if (!isset($_SESSION['order_completed']) || !$_SESSION['order_completed']) {
    header("Location: shop.php");
    exit();
}

$order_id = $_SESSION['order_id'] ?? 0;
unset($_SESSION['order_completed']);
unset($_SESSION['order_id']);

include 'includes/config.php';
include 'includes/header.php';
?>

<style>
.confirmation-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 30px;
    background: rgba(40, 30, 45, 0.8);
    border-radius: 8px;
    border: 1px solid #4CAF50;
    box-shadow: 0 4px 20px rgba(76, 175, 80, 0.3);
    text-align: center;
    color: #f0e6d2;
}

.confirmation-icon {
    font-size: 4rem;
    color: #4CAF50;
    margin-bottom: 20px;
}

.confirmation-title {
    color: #4CAF50;
    margin-bottom: 20px;
}

.order-number {
    font-size: 1.5rem;
    margin: 20px 0;
    color: #CD5C5C;
}

.order-details {
    margin-top: 30px;
    text-align: left;
    background: rgba(30, 20, 35, 0.5);
    padding: 20px;
    border-radius: 6px;
}

.btn-continue {
    display: inline-block;
    margin-top: 30px;
    padding: 12px 30px;
    background: linear-gradient(to right, #8B4513, #CD5C5C);
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-continue:hover {
    background: linear-gradient(to right, #CD5C5C, #8B4513);
    transform: translateY(-2px);
}
</style>

<div class="confirmation-container">
    <div class="confirmation-icon">
        <i class="fas fa-check-circle"></i>
    </div>
    <h1 class="confirmation-title">Order Confirmed!</h1>
    <p>Thank you for your purchase. Your order has been received and is being processed.</p>
    
    <div class="order-number">
        Order #<?= $order_id ?>
    </div>
    
    <p>You'll receive a confirmation email shortly.</p>
    
    <a href="account.php?tab=orders" class="btn-continue">View Your Orders</a>
</div>

<?php include 'includes/footer.php'; ?>