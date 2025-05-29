<?php 
$page_title = "My Orders";
include 'includes/header.php';
include 'includes/config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM orders WHERE user_id = $user_id";
$orders = $conn->query($sql);
?>

<style>
.order-card {
    border: 1px solid #ddd;
    padding: 20px;
    margin-bottom: 20px;
}
</style>

<div class="container">
    <h1>My Orders</h1>
    
    <?php while($order = $orders->fetch_assoc()): ?>
    <div class="order-card">
        <h3>Order #<?= $order['id'] ?></h3>
        <p>Date: <?= date('M j, Y', strtotime($order['created_at'])) ?></p>
        <p>Total: ₱<?= $order['total'] ?></p>
        <a href="order-details.php?id=<?= $order['id'] ?>" class="btn">View Details</a>
    </div>
    <?php endwhile; ?>
</div>

<?php include 'includes/footer.php'; ?>