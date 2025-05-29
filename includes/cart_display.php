<?php
// Get cart items from database
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.price, p.image, uc.quantity 
    FROM user_carts uc
    JOIN products p ON uc.product_id = p.id
    WHERE uc.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<?php if (!empty($cart_items)): ?>
    <div class="cart-items">
        <?php foreach ($cart_items as $item): ?>
            <div class="cart-item">
                <?php if ($item['image']): ?>
                    <img src="uploads/products/<?= htmlspecialchars($item['image']) ?>" 
                         alt="<?= htmlspecialchars($item['name']) ?>">
                <?php else: ?>
                    <div style="width: 60px; height: 60px; background: var(--sunset-darker); display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                        <i class="fas fa-image" style="font-size: 1.5rem; color: var(--sunset-text); opacity: 0.3;"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <p>Price: ₱<?= number_format($item['price'], 2) ?></p>
                    <p>Quantity: <?= $item['quantity'] ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>Your cart is empty. <a href="shop.php">Start shopping</a></p>
<?php endif; ?>