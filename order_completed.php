<?php
session_start();
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Verify order belongs to user and is completed
$stmt = $conn->prepare("
    SELECT o.*, 
           GROUP_CONCAT(p.name) as product_names,
           GROUP_CONCAT(oi.quantity) as quantities
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.id = ? AND o.user_id = ? AND o.status = 'completed'
    GROUP BY o.id
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: account.php?tab=orders&error=invalid_order");
    exit();
}

include 'includes/header.php';
?>

<style>
.success-container {
    max-width: 800px;
    margin: 3rem auto;
    padding: 2rem;
    background: rgba(30, 20, 35, 0.8);
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(139, 69, 19, 0.3);
    color: #f0e6d2;
}

.success-header {
    text-align: center;
    margin-bottom: 2rem;
}

.success-icon {
    width: 80px;
    height: 80px;
    background: rgba(40, 167, 69, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: #28a745;
    font-size: 2rem;
}

.success-title {
    color: #28a745;
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
}

.success-message {
    color: rgba(240, 230, 210, 0.7);
    font-size: 1.1rem;
}

.order-details {
    background: rgba(22, 33, 62, 0.5);
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 2rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.8rem 0;
    border-bottom: 1px solid rgba(139, 69, 19, 0.2);
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: rgba(240, 230, 210, 0.7);
    font-weight: 500;
}

.detail-value {
    color: #f0e6d2;
    font-weight: 600;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    justify-content: center;
}

.btn {
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-pink));
    color: white;
}

.btn-outline {
    border: 1px solid var(--sunset-orange);
    color: var(--sunset-orange);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.payment-verified {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-weight: 500;
    margin-top: 1rem;
}

.payment-verified i {
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .success-container {
        margin: 2rem;
        padding: 1.5rem;
    }

    .action-buttons {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<div class="success-container">
    <div class="success-header">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h1 class="success-title">Payment Successful!</h1>
        <p class="success-message">Your order has been confirmed and is being processed.</p>
    </div>

    <div class="order-details">
        <div class="detail-row">
            <span class="detail-label">Order Number</span>
            <span class="detail-value">#<?= $order_id ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Payment Method</span>
            <span class="detail-value"><?= htmlspecialchars($order['payment_method']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Total Amount</span>
            <span class="detail-value">₱<?= number_format($order['total'], 2) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Order Date</span>
            <span class="detail-value"><?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Shipping Address</span>
            <span class="detail-value">
                <?= htmlspecialchars($order['shipping_address']) ?><br>
                <?= htmlspecialchars($order['shipping_city']) ?>, 
                <?= htmlspecialchars($order['shipping_state']) ?> 
                <?= htmlspecialchars($order['shipping_zip']) ?>
            </span>
        </div>
        <div class="payment-verified">
            <i class="fas fa-shield-alt"></i>
            Payment Verified at <?= date('g:i A', strtotime($order['payment_verified_at'])) ?>
        </div>
    </div>

    <div class="action-buttons">
        <a href="account.php?tab=orders" class="btn btn-primary">View Order History</a>
        <a href="shop.php" class="btn btn-outline">Continue Shopping</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 