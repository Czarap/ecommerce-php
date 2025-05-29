<?php
require_once __DIR__ . '/../includes/config.php';
require_once 'includes/admin_header.php';

// Get statistics
$stats = [
    'total_products' => $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'")->fetch_assoc()['count'],
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_categories' => $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'],
    'low_stock' => $conn->query("SELECT COUNT(*) as count FROM products WHERE stock <= 10 AND status = 'active'")->fetch_assoc()['count']
];

// Get recent products
$recent_products = $conn->query("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 5");

// Get recent users
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
?>

<div class="admin-header">
    <h1 class="h3 mb-0 text-white">Dashboard Overview</h1>
</div>

<div class="row g-4 mb-4">
    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-2">Total Products</h6>
                        <h4><?php echo $stats['total_products']; ?></h4>
                    </div>
                    <div class="ms-3">
                        <i class="bi bi-box-seam fs-1 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-2">Total Users</h6>
                        <h4><?php echo $stats['total_users']; ?></h4>
                    </div>
                    <div class="ms-3">
                        <i class="bi bi-people fs-1 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-2">Categories</h6>
                        <h4><?php echo $stats['total_categories']; ?></h4>
                    </div>
                    <div class="ms-3">
                        <i class="bi bi-tags fs-1 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-2">Low Stock Items</h6>
                        <h4><?php echo $stats['low_stock']; ?></h4>
                    </div>
                    <div class="ms-3">
                        <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Products -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="table-header">
                <h5>Recent Products</h5>
                <a href="admin_products.php" class="btn-view-all">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($product = $recent_products->fetch_assoc()): ?>
                            <tr>
                                <td class="text-white"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="text-white">₱<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <?php if($product['stock'] <= 10): ?>
                                        <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?php echo $product['stock']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="table-header">
                <h5>Recent Users</h5>
                <a href="admin_users.php" class="btn-view-all">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = $recent_users->fetch_assoc()): ?>
                            <tr>
                                <td class="text-white"><?php echo htmlspecialchars($user['name']); ?></td>
                                <td class="text-white"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if($user['role'] === 'admin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Customer</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- overider bootsrap bg -->
<style>
.table {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(26, 26, 46, 0.1);
    --bs-table-hover-bg: rgba(255, 77, 109, 0.15);
    border-color: rgba(255, 123, 37, 0.2) !important;
}

.table td, .table th, .table tr {
    background-color: rgba(22, 33, 62, 0.7) !important;
    color: var(--text-light) !important;
    border-color: rgba(255, 123, 37, 0.15) !important;
}

.table-hover tbody tr:hover td {
    background-color: rgba(40, 40, 72, 0.9) !important;
    box-shadow: 0 2px 8px rgba(255, 77, 109, 0.2);
}

.table thead th {
    background: linear-gradient(135deg, 
        rgba(255, 123, 37, 0.25), 
        rgba(255, 77, 109, 0.25)) !important;
    color: var(--sunset-orange) !important;
    border-bottom: 2px solid var(--sunset-pink) !important;
}
</style>
<?php require_once 'includes/admin_footer.php'; ?>