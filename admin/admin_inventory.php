<?php
require_once __DIR__ . '/../includes/config.php';
require_once 'includes/admin_header.php';

// Handle stock updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_stock') {
        $id = intval($_POST['id']);
        $stock = intval($_POST['stock']);
        $reorder_point = intval($_POST['reorder_point']);
        
        $stmt = $conn->prepare("UPDATE products SET stock = ?, reorder_point = ? WHERE id = ?");
        $stmt->bind_param("iii", $stock, $reorder_point, $id);
        $stmt->execute();
        
        header('Location: admin_inventory.php');
        exit();
    }
}

// Get inventory with categories
$inventory = $conn->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active'
    ORDER BY p.stock ASC
")->fetch_all(MYSQLI_ASSOC);

// Get low stock items count
$low_stock_count = 0;
foreach ($inventory as $item) {
    if ($item['stock'] <= ($item['reorder_point'] ?? 10)) {
        $low_stock_count++;
    }
}
?>

<div class="admin-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Inventory Management</h1>
        <div class="d-flex gap-2">
            <?php if ($low_stock_count > 0): ?>
            <div class="alert alert-warning d-flex align-items-center m-0 px-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo $low_stock_count; ?> items need restock
            </div>
            <?php endif; ?>
            <button type="button" class="btn btn-sunset" onclick="exportInventory()">
                <i class="bi bi-download"></i> Export
            </button>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="inventoryTable">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Reorder Point</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($item['image']): ?>
                                    <img src="../uploads/products/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div class="bg-secondary" style="width: 40px; height: 40px; border-radius: 4px;"></div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="text-muted small">SKU: <?php echo $item['id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td>
                            <span class="fw-bold <?php echo $item['stock'] <= ($item['reorder_point'] ?? 10) ? 'text-danger' : 'text-success'; ?>">
                                <?php echo $item['stock']; ?>
                            </span>
                        </td>
                        <td><?php echo $item['reorder_point'] ?? 10; ?></td>
                        <td>
                            <?php if ($item['stock'] <= 0): ?>
                                <span class="badge bg-danger">Out of Stock</span>
                            <?php elseif ($item['stock'] <= ($item['reorder_point'] ?? 10)): ?>
                                <span class="badge bg-warning">Low Stock</span>
                            <?php else: ?>
                                <span class="badge bg-success">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateStockModal<?php echo $item['id']; ?>">
                                <i class="bi bi-pencil"></i> Update Stock
                            </button>
                        </td>
                    </tr>

                    <!-- Update Stock Modal -->
                    <div class="modal fade" id="updateStockModal<?php echo $item['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark">
                                <div class="modal-header border-sunset">
                                    <h5 class="modal-title">Update Stock - <?php echo htmlspecialchars($item['name']); ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="" method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="update_stock">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Current Stock</label>
                                            <input type="number" class="form-control bg-dark text-light" name="stock" value="<?php echo $item['stock']; ?>" required min="0">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Reorder Point</label>
                                            <input type="number" class="form-control bg-dark text-light" name="reorder_point" value="<?php echo $item['reorder_point'] ?? 10; ?>" required min="0">
                                            <div class="form-text text-muted">Alert will show when stock falls below this number</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-sunset">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-sunset">Update Stock</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportInventory() {
    const table = document.getElementById('inventoryTable');
    const rows = Array.from(table.querySelectorAll('tr'));
    
    let csv = [];
    // Add header
    csv.push(['Product', 'Category', 'Current Stock', 'Reorder Point', 'Status'].join(','));
    
    // Add rows
    rows.slice(1).forEach(row => {
        const cells = Array.from(row.querySelectorAll('td'));
        const productName = cells[0].querySelector('.fw-bold').textContent.trim();
        const category = cells[1].textContent.trim();
        const stock = cells[2].textContent.trim();
        const reorderPoint = cells[3].textContent.trim();
        const status = cells[4].querySelector('.badge').textContent.trim();
        
        csv.push([productName, category, stock, reorderPoint, status].join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'inventory_report_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}
</script>

<style>
.alert {
    border: 1px solid rgba(255, 193, 7, 0.3);
}
.fw-bold {
    font-weight: 600;
}
.text-success {
    color: #28a745 !important;
}
.text-danger {
    color: #dc3545 !important;
}
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