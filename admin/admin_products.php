<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Set up upload directory first
$upload_dir = '../uploads/products';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
} elseif (!is_writable($upload_dir)) {
    // Try to make the directory writable if it exists but isn't writable
    chmod($upload_dir, 0777);
}

// Handle form submissions first, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $redirect = false;
        
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $price = floatval($_POST['price']);
                $stock = intval($_POST['stock']);
                $category_id = intval($_POST['category_id']);

                // Handle image upload
                $image = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    $filename = $_FILES['image']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed)) {
                        $image = 'product_' . time() . '.' . $ext;
                        $target_path = $upload_dir . '/' . $image;
                        
                        // Check if directory is writable
                        if (!is_writable($upload_dir)) {
                            $_SESSION['error_message'] = "Error: Upload directory is not writable. Please check permissions for: " . $upload_dir;
                            $redirect = true;
                            break;
                        }
                        
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                            $_SESSION['error_message'] = "Error: Failed to move uploaded file. Please check permissions and available disk space.";
                            $redirect = true;
                            break;
                        }
                    }
                }

                $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, category_id, image, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                $stmt->bind_param("ssdiis", $name, $description, $price, $stock, $category_id, $image);
                if($stmt->execute()) {
                    $_SESSION['success_message'] = "Product added successfully";
                } else {
                    $_SESSION['error_message'] = "Error adding product";
                }
                $redirect = true;
                break;

            case 'edit':
                $id = intval($_POST['id']);
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $price = floatval($_POST['price']);
                $stock = intval($_POST['stock']);
                $category_id = intval($_POST['category_id']);

                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    $filename = $_FILES['image']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed)) {
                        // Delete old image
                        $old_image = $conn->query("SELECT image FROM products WHERE id = $id")->fetch_assoc()['image'];
                        if ($old_image && file_exists('../uploads/products/' . $old_image)) {
                            unlink('../uploads/products/' . $old_image);
                        }

                        $image = 'product_' . time() . '.' . $ext;
                        $target_path = $upload_dir . '/' . $image;
                        
                        // Check if directory is writable
                        if (!is_writable($upload_dir)) {
                            $_SESSION['error_message'] = "Error: Upload directory is not writable";
                            $redirect = true;
                            break;
                        }
                        
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                            $_SESSION['error_message'] = "Error: Failed to move uploaded file";
                            $redirect = true;
                            break;
                        }
                        
                        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category_id=?, image=? WHERE id=?");
                        $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $category_id, $image, $id);
                    }
                } else {
                    $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category_id=? WHERE id=?");
                    $stmt->bind_param("ssdiis", $name, $description, $price, $stock, $category_id, $id);
                }
                if($stmt->execute()) {
                    $_SESSION['success_message'] = "Product updated successfully";
                } else {
                    $_SESSION['error_message'] = "Error updating product";
                }
                $redirect = true;
                break;

            case 'delete':
                $id = intval($_POST['id']);
                // Instead of deleting, mark as inactive
                $stmt = $conn->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
                $stmt->bind_param("i", $id);
                if($stmt->execute()) {
                    $_SESSION['success_message'] = "Product deleted successfully";
                } else {
                    $_SESSION['error_message'] = "Error deleting product";
                }
                $redirect = true;
                break;
        }
        
        if ($redirect) {
            header('Location: admin_products.php');
            exit();
        }
    }
}

// Include header and continue with the rest of the page
require_once 'includes/admin_header.php';

// Display any messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']);
}

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get active products with category names
$products = $conn->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Product Management</h1>
        <button type="button" class="btn btn-sunset" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-lg"></i> Add New Product
        </button>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if ($product['image']): ?>
                                <img src="../uploads/products/<?php echo $product['image']; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-thumbnail">
                            <?php else: ?>
                                <div class="product-thumbnail-placeholder">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td>₱<?php echo number_format($product['price'], 2); ?></td>
                        <td>
                            <?php if ($product['stock'] <= 10): ?>
                                <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                            <?php else: ?>
                                <span class="badge bg-success"><?php echo $product['stock']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $product['id']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Edit Product Modal -->
                    <div class="modal fade" id="editProductModal<?php echo $product['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content bg-dark">
                                <div class="modal-header border-sunset">
                                    <h5 class="modal-title">Edit Product</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control bg-dark text-light" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control bg-dark text-light" name="description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Price</label>
                                                    <input type="number" class="form-control bg-dark text-light" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Stock</label>
                                                    <input type="number" class="form-control bg-dark text-light" name="stock" value="<?php echo $product['stock']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Category</label>
                                                    <select class="form-select bg-dark text-light" name="category_id" required>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($category['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Image</label>
                                            <input type="file" class="form-control bg-dark text-light" name="image" accept="image/*">
                                            <?php if ($product['image']): ?>
                                                <div class="mt-2">
                                                    <img src="../uploads/products/<?php echo $product['image']; ?>" alt="Current image" style="max-height: 100px;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-sunset">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-sunset">Update Product</button>
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

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-sunset">
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control bg-dark text-light" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control bg-dark text-light" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" class="form-control bg-dark text-light" name="price" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control bg-dark text-light" name="stock" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select bg-dark text-light" name="category_id" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" class="form-control bg-dark text-light" name="image" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer border-sunset">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sunset">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.append(form);
        form.submit();
    }
}
</script>

<style>
.modal-content {
    border: 1px solid var(--sunset-purple);
}
.border-sunset {
    border-color: var(--sunset-purple) !important;
}
.form-control:focus, .form-select:focus {
    border-color: var(--sunset-orange);
    box-shadow: 0 0 0 0.25rem rgba(255, 123, 37, 0.25);
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