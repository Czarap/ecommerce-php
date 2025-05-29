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
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get product details
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.seller_id = ?
");
$stmt->bind_param("ii", $product_id, $seller_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    $_SESSION['error_message'] = "Product not found or you don't have permission to edit it.";
    header('Location: dashboard.php');
    exit();
}

// Get categories for dropdown
$categories = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category_id = intval($_POST['category_id']);
    $status = sanitize($_POST['status']);
    
    // Handle image upload
    $image = $product['image']; // Keep existing image by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Delete old image if it exists
            if (!empty($product['image'])) {
                $old_image_path = '../uploads/products/' . $product['image'];
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            
            $image = 'product_' . time() . '.' . $ext;
            $upload_dir = '../uploads/products';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $target_path = $upload_dir . '/' . $image;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $_SESSION['error_message'] = "Error uploading image";
                $image = $product['image']; // Keep old image if upload fails
            }
        }
    }
    
    // Update product
    $stmt = $conn->prepare("
        UPDATE products 
        SET name = ?, description = ?, price = ?, 
            stock = ?, category_id = ?, image = ?,
            status = ?
        WHERE id = ? AND seller_id = ?
    ");
    $stmt->bind_param("ssdiissii", $name, $description, $price, $stock, $category_id, $image, $status, $product_id, $seller_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Product updated successfully";
        header('Location: dashboard.php');
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating product";
    }
}

$page_title = "Edit Product";
include '../includes/header.php';
?>

<style>
.edit-product-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: rgba(26, 26, 46, 0.8);
    border-radius: 15px;
    border: 1px solid var(--sunset-orange);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    color: var(--sunset-light);
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    background: rgba(22, 33, 62, 0.5);
    border: 1px solid var(--sunset-purple);
    border-radius: 8px;
    color: var(--sunset-text);
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--sunset-orange);
    box-shadow: 0 0 0 2px rgba(255, 123, 37, 0.2);
}

textarea.form-control {
    min-height: 150px;
    resize: vertical;
}

.current-image {
    max-width: 200px;
    border-radius: 8px;
    margin: 1rem 0;
}

.submit-btn {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 77, 109, 0.3);
}

.back-btn {
    display: inline-block;
    color: var(--sunset-orange);
    text-decoration: none;
    margin-bottom: 1rem;
    transition: all 0.3s;
}

.back-btn:hover {
    color: var(--sunset-light);
    transform: translateX(-5px);
}

.image-preview {
    width: 200px;
    height: 200px;
    border-radius: 8px;
    overflow: hidden;
    margin: 1rem 0;
    background: #2a2a3a;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>

<div class="edit-product-container">
    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    
    <h1 class="mb-4">Edit Product</h1>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error_message'] ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="name" class="form-control" 
                   value="<?= htmlspecialchars($product['name']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" required><?= htmlspecialchars($product['description']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Price (₱)</label>
            <input type="number" name="price" class="form-control" 
                   value="<?= number_format($product['price'], 2, '.', '') ?>" 
                   step="0.01" min="0" required>
        </div>
        
        <div class="form-group">
            <label>Stock</label>
            <input type="number" name="stock" class="form-control" 
                   value="<?= intval($product['stock']) ?>" 
                   min="0" required>
        </div>
        
        <div class="form-group">
            <label>Category</label>
            <select name="category_id" class="form-control" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>" 
                            <?= $category['id'] == $product['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Product Image</label>
            <div class="image-preview">
                <?php if (!empty($product['image'])): ?>
                    <img src="../uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         id="imagePreview">
                <?php else: ?>
                    <i class="fas fa-image fa-3x" style="color: #666;" id="imagePlaceholder"></i>
                    <img src="" alt="" style="display: none;" id="imagePreview">
                <?php endif; ?>
            </div>
            <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
            <small class="text-muted">Leave empty to keep current image</small>
        </div>
        
        <button type="submit" class="submit-btn">
            <i class="fas fa-save"></i> Update Product
        </button>
    </form>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('imagePlaceholder');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) {
                placeholder.style.display = 'none';
            }
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>