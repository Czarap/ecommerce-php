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

// Get seller's products (for count only)
$stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM products p 
    WHERE p.seller_id = ?
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$product_count = $stmt->get_result()->fetch_assoc()['count'];

// Get seller's orders 
$stmt = $conn->prepare("
    SELECT o.*, u.name as customer_name,
           GROUP_CONCAT(DISTINCT p.name SEPARATOR '||') as product_names,
           GROUP_CONCAT(DISTINCT oi.quantity SEPARATOR '||') as quantities,
           COALESCE(o.status, 'pending') as status,
           COALESCE(o.created_at, CURRENT_TIMESTAMP) as created_at
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ?
    GROUP BY o.id, o.user_id, o.total, o.status, o.created_at, o.shipping_name, 
             o.shipping_email, o.shipping_address, o.shipping_city, o.shipping_state, 
             o.shipping_zip, o.payment_method, o.tracking_number, o.updated_at
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total earnings
$stmt = $conn->prepare("
    SELECT SUM(oi.price * oi.quantity) as total
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE p.seller_id = ?
    AND o.status = 'delivered'
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$earnings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$page_title = "Seller Dashboard";
include '../includes/header.php';
?>

<style>
.seller-dashboard {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(26, 26, 46, 0.8);
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid var(--sunset-orange);
    text-align: center;
}

.stat-card h3 {
    font-size: 2rem;
    color: var(--sunset-orange);
    margin-bottom: 0.5rem;
}

.section-title {
    margin: 2rem 0 1rem;
    color: var(--sunset-light);
    border-bottom: 2px solid var(--sunset-orange);
    padding-bottom: 0.5rem;
}

.orders-table {
    width: 100%;
    margin-top: 1rem;
    border-collapse: collapse;
}

.orders-table th,
.orders-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 123, 37, 0.2);
}

.orders-table th {
    background: rgba(255, 123, 37, 0.1);
    color: var(--sunset-orange);
}

.action-btn {
    padding: 0.5rem 1rem;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    margin: 0.2rem;
}

.edit-btn {
    background: var(--sunset-orange);
    color: white;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.add-product-btn {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 1rem;
}

.add-product-btn:hover {
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

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.product-card {
    background: rgba(26, 26, 46, 0.8);
    border-radius: 10px;
    padding: 1rem;
    border: 1px solid var(--sunset-orange);
    transition: all 0.3s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(255, 123, 37, 0.2);
}

.product-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.product-name {
    color: var(--sunset-light);
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.product-category {
    color: var(--sunset-orange);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.product-price {
    color: var(--sunset-text);
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.product-stock {
    color: var(--sunset-text);
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.product-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: auto;
}

.product-actions .action-btn {
    flex: 1;
    text-align: center;
}

.delete-btn {
    background: #dc3545;
    color: white;
}
</style>

<div class="seller-dashboard">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Seller Dashboard</h1>
        <a href="add_product.php" class="add-product-btn">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= $product_count ?></h3>
            <p>Total Products</p>
        </div>
        <div class="stat-card">
            <h3><?= count($orders) ?></h3>
            <p>Total Orders</p>
        </div>
        <div class="stat-card">
            <h3>₱<?= number_format($earnings, 2) ?></h3>
            <p>Total Earnings</p>
        </div>
    </div>

    <h2 class="section-title">Recent Orders</h2>
    <div class="table-responsive">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></td>
                    <td>
                        <?php
                        $products = explode('||', $order['product_names'] ?? '');
                        $quantities = explode('||', $order['quantities'] ?? '');
                        if (!empty($products) && $products[0] !== '') {
                            foreach ($products as $i => $product) {
                                if (isset($quantities[$i])) {
                                    echo htmlspecialchars(trim($product)) . ' (x' . intval($quantities[$i]) . ')<br>';
                                }
                            }
                        } else {
                            echo "No products found";
                        }
                        ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?= strtolower($order['status'] ?? 'pending') ?>">
                            <?= ucfirst($order['status'] ?? 'pending') ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y', strtotime($order['created_at'] ?? 'now')) ?></td>
                    <td>
                        <a href="view_order.php?id=<?= $order['id'] ?>" class="action-btn edit-btn">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2 class="section-title">Your Products</h2>
    <div class="product-grid">
        <?php 
        // Get seller's products
        $stmt = $conn->prepare("
            SELECT p.*, 
                   COALESCE(c.name, 'Uncategorized') as category_name,
                   COALESCE(p.status, 'active') as status
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.seller_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $seller_id);
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($products as $product): 
        ?>
        <div class="product-card" data-product-id="<?= $product['id'] ?>">
            <?php if (!empty($product['image'])): ?>
                <img src="../uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     class="product-image">
            <?php else: ?>
                <div class="product-image" style="background: #2a2a3a; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-image fa-3x" style="color: #666;"></i>
                </div>
            <?php endif; ?>
            
            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
            <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
            <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
            <div class="product-stock">
                Stock: <?= intval($product['stock']) ?>
                <span class="status-badge status-<?= strtolower($product['status']) ?>" style="float: right;">
                    <?= ucfirst($product['status']) ?>
                </span>
            </div>
            
            <div class="product-actions">
                <a href="edit_product.php?id=<?= $product['id'] ?>" class="action-btn edit-btn">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button onclick="deleteProduct(<?= $product['id'] ?>)" class="action-btn delete-btn">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
async function deleteProduct(productId) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }

    try {
        const response = await fetch('delete_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}`
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.status === 'success') {
            // Remove the product card from the UI
            const productCard = document.querySelector(`[data-product-id="${productId}"]`);
            if (productCard) {
                productCard.style.transition = 'opacity 0.3s ease';
                productCard.style.opacity = '0';
                setTimeout(() => {
                    productCard.remove();
                    // If no products left, reload the page
                    if (document.querySelectorAll('.product-card').length === 0) {
                        location.reload();
                    }
                }, 300);
            } else {
                location.reload();
            }
        } else {
            throw new Error(data.message || 'Failed to delete product');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(`Failed to delete product: ${error.message}`);
    }
}
</script>

<?php include '../includes/footer.php'; ?> 