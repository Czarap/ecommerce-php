<?php
session_start();
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("Unauthorized access");
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $conn->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Get order items
$stmt = $conn->prepare("SELECT oi.*, p.name, p.image 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="order-details">
    <div class="order-info">
        <h3>Customer Information</h3>
        <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
        
        <h3 style="margin-top: 1.5rem;">Shipping Details</h3>
        <p><strong>Name:</strong> <?= htmlspecialchars($order['shipping_name']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
        <p><strong>City:</strong> <?= htmlspecialchars($order['shipping_city']) ?></p>
        <p><strong>State/Zip:</strong> <?= htmlspecialchars($order['shipping_state']) ?> <?= htmlspecialchars($order['shipping_zip']) ?></p>
        <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
    </div>
    
    <div class="order-items" style="margin-top: 2rem;">
        <h3>Order Items</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
            <thead>
                <tr style="background: rgba(255, 123, 37, 0.1);">
                    <th style="padding: 0.8rem; text-align: left;">Product</th>
                    <th style="padding: 0.8rem; text-align: right;">Price</th>
                    <th style="padding: 0.8rem; text-align: center;">Qty</th>
                    <th style="padding: 0.8rem; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr style="border-bottom: 1px solid rgba(255, 123, 37, 0.1);">
                        <td style="padding: 0.8rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div class="order-item">
                                    <?php if ($item['image']): ?>
                                        <img src="uploads/products/<?= htmlspecialchars($item['image']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--sunset-darker); display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                            <i class="fas fa-image" style="font-size: 1.2rem; color: var(--sunset-text); opacity: 0.3;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span><?= htmlspecialchars($item['name']) ?></span>
                            </div>
                        </td>
                        <td style="padding: 0.8rem; text-align: right;">₱<?= number_format($item['price'], 2) ?></td>
                        <td style="padding: 0.8rem; text-align: center;"><?= $item['quantity'] ?></td>
                        <td style="padding: 0.8rem; text-align: right;">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="padding: 0.8rem; text-align: right; font-weight: bold;">Total:</td>
                    <td style="padding: 0.8rem; text-align: right; font-weight: bold;">₱<?= number_format($order['total'], 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>