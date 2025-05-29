<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure user is a seller
if (!isLoggedIn() || !isSeller()) {
    header('Location: ../login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category_id = intval($_POST['category_id']);
    $seller_id = $_SESSION['user_id'];
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $image = 'product_' . time() . '.' . $ext;
            $upload_dir = '../uploads/products';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $target_path = $upload_dir . '/' . $image;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                // Image uploaded successfully
            } else {
                $_SESSION['error_message'] = "Error uploading image";
                $image = '';
            }
        }
    }
    
    // Insert product
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, category_id, image, seller_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssdiisi", $name, $description, $price, $stock, $category_id, $image, $seller_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Product added successfully";
        header('Location: dashboard.php');
        exit();
    } else {
        $_SESSION['error_message'] = "Error adding product";
    }
}

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$page_title = "Add New Product";
include '../includes/header.php';
?>

<style>
.add-product-container {
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
    margin-bottom: 0.5rem;
    color: var(--sunset-light);
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px solid rgba(255, 123, 37, 0.3);
    background: rgba(40, 30, 45, 0.8);
    color: var(--sunset-light);
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--sunset-orange);
    box-shadow: 0 0 0 2px rgba(255, 123, 37, 0.2);
}

.submit-btn {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 8px;
    cursor: pointer;
    width: 100%;
    font-size: 1.1rem;
    transition: all 0.3s;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 77, 109, 0.3);
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
}

.image-preview {
    width: 200px;
    height: 200px;
    border-radius: 10px;
    border: 2px dashed rgba(255, 123, 37, 0.3);
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.image-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}

.image-preview i {
    font-size: 3rem;
    color: rgba(255, 123, 37, 0.3);
}
</style>

<div class="add-product-container">
    <h1 class="mb-4">Add New Product</h1>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" id="name" name="name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
        </div>

        <div class="form-group">
            <label for="price">Price (₱)</label>
            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required>
        </div>

        <div class="form-group">
            <label for="stock">Stock Quantity</label>
            <input type="number" id="stock" name="stock" class="form-control" min="0" required>
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" class="form-control" required>
                <option value="">Select a category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="image">Product Image</label>
            <input type="file" id="image" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
            <div class="image-preview" id="imagePreview">
                <i class="fas fa-image"></i>
            </div>
        </div>

        <button type="submit" class="submit-btn">
            <i class="fas fa-plus"></i> Add Product
        </button>
    </form>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            preview.appendChild(img);
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '<i class="fas fa-image"></i>';
    }
}
</script>

<?php include '../includes/footer.php'; ?> 