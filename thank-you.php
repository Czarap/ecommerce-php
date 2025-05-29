<?php 
$page_title = "Thank You";
include 'includes/header.php';
?>

<style>
.thank-you-container {
    text-align: center;
    padding: 50px 20px;
}

.order-details {
    max-width: 600px;
    margin: 30px auto;
    padding: 20px;
    border: 1px solid #ddd;
}
</style>

<div class="container thank-you-container">
    <h1>Thank You for Your Order!</h1>
    <p>Your order has been placed successfully.</p>
    
    <div class="order-details">
        <h3>Order Details</h3>
        <p>Order ID: #<?= $_GET['order_id'] ?></p>
        <p>Total: $<?= $_GET['total'] ?></p>
    </div>

    <a href="orders.php" class="btn">View Orders</a>
</div>

<?php include 'includes/footer.php'; ?>