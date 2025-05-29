<?php
// Must be first line
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

include 'includes/config.php';

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
    $stock = intval($_POST['stock']);
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "images/products/";
        $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $image = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $image;
        
        // Check if image file is valid
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false && move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // File uploaded successfully
        } else {
            $error = "Invalid image file";
        }
    }

    if (!isset($error)) {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, original_price, image, stock) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddss", $name, $description, $price, $original_price, $image, $stock);
        if ($stmt->execute()) {
            $success = "Product added successfully!";
        } else {
            $error = "Error adding product: " . $conn->error;
        }
    }
}

// Handle product deletion
if (isset($_GET['delete_product'])) {
    $product_id = intval($_GET['delete_product']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) {
        $success = "Product deleted successfully!";
    } else {
        $error = "Error deleting product: " . $conn->error;
    }
}

// Get all products
$products = $conn->query("SELECT * FROM products ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get all orders
$orders = $conn->query("SELECT o.*, u.name as customer_name 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get order items for each order
foreach ($orders as &$order) {
    $stmt = $conn->prepare("SELECT oi.*, p.name, p.image 
                          FROM order_items oi 
                          JOIN products p ON oi.product_id = p.id 
                          WHERE oi.order_id = ?");
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
unset($order);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
    /* Dark Sunset Admin Theme */
    :root {
        --sunset-dark: #1a1a2e;
        --sunset-darker: #16213e;
        --sunset-orange: #ff7b25;
        --sunset-pink: #ff4d6d;
        --sunset-purple: #6a2c70;
        --sunset-red: #e94560;
        --sunset-yellow: #ffd32d;
        --text-light: #f8f9fa;
        --text-muted: #adb5bd;
        --success-color: #28a745;
        --error-color: #dc3545;
    }

    body {
        background: linear-gradient(135deg, var(--sunset-dark), var(--sunset-darker));
        color: var(--text-light);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
        margin: 0;
        padding: 0;
        line-height: 1.6;
    }

    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    .admin-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .admin-title {
        font-size: 2.5rem;
        background: linear-gradient(to right, var(--sunset-orange), var(--sunset-pink));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 0.5rem;
    }

    .admin-subtitle {
        color: var(--text-muted);
        font-size: 1.1rem;
    }

    .tab-container {
        display: flex;
        margin-bottom: 2rem;
        border-bottom: 1px solid rgba(255, 123, 37, 0.2);
    }

    .tab {
        padding: 0.8rem 1.5rem;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }

    .tab.active {
        border-bottom-color: var(--sunset-pink);
        color: var(--sunset-pink);
        font-weight: bold;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .admin-section {
        background: rgba(26, 26, 46, 0.8);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 2.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 77, 109, 0.2);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        border-bottom: 1px solid rgba(255, 123, 37, 0.2);
        padding-bottom: 1rem;
    }

    .btn {
        display: inline-block;
        padding: 0.8rem 1.5rem;
        background: linear-gradient(to right, var(--sunset-orange), var(--sunset-pink));
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid var(--sunset-pink);
        color: var(--sunset-pink);
    }

    .btn-danger {
        background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 77, 109, 0.4);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-light);
    }

    input[type="text"],
    input[type="number"],
    input[type="file"],
    textarea,
    select {
        width: 100%;
        padding: 0.8rem;
        background: rgba(22, 33, 62, 0.5);
        border: 1px solid rgba(255, 123, 37, 0.3);
        border-radius: 8px;
        color: var(--text-light);
        font-size: 1rem;
        transition: border 0.3s ease;
    }

    textarea {
        min-height: 120px;
        resize: vertical;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    input[type="file"]:focus,
    textarea:focus,
    select:focus {
        outline: none;
        border-color: var(--sunset-pink);
        box-shadow: 0 0 0 2px rgba(255, 77, 109, 0.2);
    }

    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }

    .alert-success {
        background-color: rgba(40, 167, 69, 0.2);
        border: 1px solid rgba(40, 167, 69, 0.3);
        color: var(--success-color);
    }

    .alert-danger {
        background-color: rgba(220, 53, 69, 0.2);
        border: 1px solid rgba(220, 53, 69, 0.3);
        color: var(--error-color);
    }

    /* Products Table */
    .products-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1.5rem;
    }

    .products-table th {
        text-align: left;
        padding: 1rem;
        background: rgba(255, 123, 37, 0.2);
        color: var(--sunset-orange);
    }

    .products-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(255, 123, 37, 0.1);
    }

    .products-table tr:hover {
        background: rgba(255, 123, 37, 0.05);
    }

    .product-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 5px;
    }

    /* Orders Table */
    .orders-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1.5rem;
    }

    .orders-table th {
        text-align: left;
        padding: 1rem;
        background: rgba(255, 123, 37, 0.2);
        color: var(--sunset-orange);
    }

    .orders-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(255, 123, 37, 0.1);
    }

    .orders-table tr:hover {
        background: rgba(255, 123, 37, 0.05);
    }

    .order-status {
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
        display: inline-block;
    }

    .order-status.pending {
        background-color: rgba(255, 193, 7, 0.2);
        color: #ffc107;
    }

    .order-status.completed {
        background-color: rgba(40, 167, 69, 0.2);
        color: #28a745;
    }

    .order-status.cancelled {
        background-color: rgba(220, 53, 69, 0.2);
        color: #dc3545;
    }

    /* Order Details Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        overflow-y: auto;
    }

    .modal-content {
        background: var(--sunset-dark);
        margin: 2rem auto;
        padding: 2rem;
        border-radius: 10px;
        max-width: 800px;
        border: 1px solid var(--sunset-pink);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(255, 123, 37, 0.2);
    }

    .close-modal {
        background: none;
        border: none;
        color: var(--sunset-pink);
        font-size: 1.5rem;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .admin-container {
            padding: 1rem;
        }
        
        .admin-section {
            padding: 1.5rem;
        }
        
        .products-table, .orders-table {
            display: block;
            overflow-x: auto;
        }
    }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Admin Dashboard</h1>
            <p class="admin-subtitle">Manage products and orders</p>
        </div>

        <div class="tab-container">
            <div class="tab active" onclick="switchTab('products')">Products</div>
            <div class="tab" onclick="switchTab('orders')">Orders</div>
        </div>

        <div id="productsTab" class="tab-content active">
            <div class="admin-section">
                <div class="section-header">
                    <h2>Add New Product</h2>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="original_price">Original Price (optional)</label>
                        <input type="number" id="original_price" name="original_price" step="0.01" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stock Quantity</label>
                        <input type="number" id="stock" name="stock" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <input type="file" id="image" name="image" accept="image/*" required>
                    </div>
                    
                    <button type="submit" name="add_product" class="btn">Add Product</button>
                </form>
            </div>

            <div class="admin-section">
                <div class="section-header">
                    <h2>Product List</h2>
                </div>
                
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><img src="images/products/<?= htmlspecialchars($product['image']) ?>" class="product-image" alt="<?= htmlspecialchars($product['name']) ?>"></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td>$<?= number_format($product['price'], 2) ?></td>
                                <td><?= $product['stock'] ?></td>
                                <td>
                                    <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-outline">Edit</a>
                                    <a href="?delete_product=<?= $product['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="ordersTab" class="tab-content">
            <div class="admin-section">
                <div class="section-header">
                    <h2>Customer Orders</h2>
                </div>
                
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                <td>₱<?= number_format($order['total'], 2) ?></td>
                                <td><span class="order-status <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                                <td>
                                    <button class="btn btn-outline" onclick="viewOrderDetails(<?= $order['id'] ?>)">View</button>
                                    <div class="dropdown">
                                        <button class="btn">Status</button>
                                        <div class="dropdown-content">
                                            <a href="update_order_status.php?id=<?= $order['id'] ?>&status=pending">Pending</a>
                                            <a href="update_order_status.php?id=<?= $order['id'] ?>&status=completed">Completed</a>
                                            <a href="update_order_status.php?id=<?= $order['id'] ?>&status=cancelled">Cancelled</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Details #<span id="modalOrderId"></span></h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalOrderContent"></div>
        </div>
    </div>

    <script>
    // Tab switching
    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Deactivate all tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Activate selected tab
        document.getElementById(tabName + 'Tab').classList.add('active');
        event.currentTarget.classList.add('active');
    }

    // View order details
    function viewOrderDetails(orderId) {
        fetch('get_order_details.php?id=' + orderId)
            .then(response => response.text())
            .then(data => {
                document.getElementById('modalOrderId').textContent = orderId;
                document.getElementById('modalOrderContent').innerHTML = data;
                document.getElementById('orderModal').style.display = 'block';
            });
    }

    // Close modal
    function closeModal() {
        document.getElementById('orderModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('orderModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>
</body>
</html>