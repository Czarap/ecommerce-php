<?php
require_once __DIR__ . '/includes/config.php';
sellerOnly();

// Get seller's products
$seller_id = $_SESSION['user_id'];
$products = $conn->query("
    SELECT * FROM products 
    WHERE seller_id = $seller_id 
    ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get seller's orders
$orders = $conn->query("
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = $seller_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .seller-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="seller-container">
        <h1>Seller Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= count($products) ?></h3>
                <p>Your Products</p>
                <a href="../admin/admin_products.php">Manage Products</a>
            </div>
            
            <div class="stat-card">
                <h3><?= count($orders) ?></h3>
                <p>Recent Orders</p>
                <a href="#">View Orders</a>
            </div>
        </div>
        
        <h2>Your Products</h2>
        <div class="product-grid">
            <?php foreach (array_slice($products, 0, 5) as $product): ?>
            <div class="product-card">
                <img src="images/products/<?= htmlspecialchars($product['image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p>$<?= number_format($product['price'], 2) ?></p>
                <p>Stock: <?= $product['stock'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="seller-actions">
            <a href="../admin/admin_products.php" class="btn">Manage Products</a>
            <?php if (isAdmin()): ?>
                <a href="../admin/admin_dashboard.php" class="btn">Admin Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>