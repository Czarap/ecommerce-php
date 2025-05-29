<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure user is a seller
if (!isLoggedIn() || !isSeller()) {
    header('Location: ../login.php');
    exit();
}

$seller_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $tracking = isset($_POST['tracking_number']) ? trim($_POST['tracking_number']) : '';
    
    // Update order status and tracking number
    $update_stmt = $conn->prepare("
        UPDATE orders 
        SET status = ?, 
            tracking_number = ?,
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND EXISTS (
            SELECT 1 FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = orders.id AND p.seller_id = ?
        )
    ");
    
    $update_stmt->bind_param("ssii", $new_status, $tracking, $order_id, $seller_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Error updating order status.";
    }
}

// Get order details with items
$stmt = $conn->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email,
           p.name as product_name, p.image as product_image,
           oi.quantity, oi.price as item_price
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.id = ? AND p.seller_id = ?
");

$stmt->bind_param("ii", $order_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php');
    exit();
}

$order_items = $result->fetch_all(MYSQLI_ASSOC);
$order = $order_items[0]; // Get the first row for order details

$page_title = "View Order #" . $order_id;
include '../includes/header.php';
?>

<style>
.order-details {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.order-info {
    background: rgba(26, 26, 46, 0.8);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--sunset-orange);
}

.order-info h3 {
    color: var(--sunset-orange);
    margin-bottom: 1rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.info-item {
    margin-bottom: 1rem;
}

.info-item label {
    display: block;
    color: var(--text-muted);
    margin-bottom: 0.3rem;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.items-table th,
.items-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 123, 37, 0.2);
}

.items-table th {
    background: rgba(255, 123, 37, 0.1);
    color: var(--sunset-orange);
}

.product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 5px;
}

.back-btn {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 77, 109, 0.3);
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
}

.status-pending { background: #ffc107; color: #000; }
.status-processing { background: #17a2b8; color: #fff; }
.status-shipped { background: #007bff; color: #fff; }
.status-delivered { background: #28a745; color: #fff; }
.status-cancelled { background: #dc3545; color: #fff; }

.status-form {
    margin-top: 1rem;
    padding: 1rem;
    border-top: 1px solid rgba(255, 123, 37, 0.2);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--sunset-orange);
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid rgba(255, 123, 37, 0.2);
    border-radius: 5px;
    background: rgba(26, 26, 46, 0.8);
    color: white;
}

.update-btn {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.update-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 77, 109, 0.3);
}

.alert {
    padding: 1rem;
    border-radius: 5px;
    margin-bottom: 1rem;
}

.alert-success {
    background: rgba(40, 167, 69, 0.2);
    border: 1px solid #28a745;
    color: #28a745;
}

.alert-danger {
    background: rgba(220, 53, 69, 0.2);
    border: 1px solid #dc3545;
    color: #dc3545;
}
</style>

<div class="order-details">
    <div class="order-header">
        <h1>Order #<?= $order_id ?></h1>
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="order-info">
        <h3>Order Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <label>Order Date</label>
                <p><?= date('F j, Y', strtotime($order['created_at'])) ?></p>
            </div>
            <div class="info-item">
                <label>Last Updated</label>
                <p><?= date('F j, Y', strtotime($order['updated_at'] ?? $order['created_at'])) ?></p>
            </div>
            <div class="info-item">
                <label>Status</label>
                <span class="status-badge status-<?= strtolower($order['status']) ?>">
                    <?= ucfirst($order['status']) ?>
                </span>
            </div>
            <?php if ($order['tracking_number']): ?>
            <div class="info-item">
                <label>Tracking Number</label>
                <p><?= htmlspecialchars($order['tracking_number']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <form method="POST" class="status-form">
            <div class="form-group">
                <label for="status">Update Order Status</label>
                <select name="status" id="status" class="form-control" required>
                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tracking_number">Tracking Number (optional)</label>
                <input type="text" name="tracking_number" id="tracking_number" class="form-control" 
                       value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>"
                       placeholder="Enter tracking number">
            </div>

            <button type="submit" name="update_status" class="update-btn">
                <i class="fas fa-sync-alt"></i> Update Status
            </button>
        </form>
    </div>

    <div class="order-info">
        <h3>Shipping Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <label>Shipping Address</label>
                <p><?= htmlspecialchars($order['shipping_address']) ?></p>
            </div>
            <div class="info-item">
                <label>City</label>
                <p><?= htmlspecialchars($order['shipping_city']) ?></p>
            </div>
            <div class="info-item">
                <label>State</label>
                <p><?= htmlspecialchars($order['shipping_state']) ?></p>
            </div>
            <div class="info-item">
                <label>ZIP Code</label>
                <p><?= htmlspecialchars($order['shipping_zip']) ?></p>
            </div>
        </div>
    </div>

    <div class="order-info">
        <h3>Order Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Image</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                foreach ($order_items as $item): 
                    $item_total = $item['item_price'] * $item['quantity'];
                    $total += $item_total;
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td>
                        <?php if ($item['product_image']): ?>
                            <img src="../uploads/products/<?= htmlspecialchars($item['product_image']) ?>" 
                                 alt="<?= htmlspecialchars($item['product_name']) ?>"
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="background: #eee; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>₱<?= number_format($item['item_price'], 2) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>₱<?= number_format($item_total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4" style="text-align: right;"><strong>Total:</strong></td>
                    <td><strong>₱<?= number_format($total, 2) ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// Add confirmation for status changes
document.querySelector('form').addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to update the order status?')) {
        e.preventDefault();
    }
});
</script>

<?php include '../includes/footer.php'; ?> 