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

// Get categories with product counts for this seller
$categories = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.seller_id = $seller_id
    WHERE c.status = 'active'
    GROUP BY c.id
    ORDER BY c.name
")->fetch_all(MYSQLI_ASSOC);

$page_title = "Categories";
include '../includes/header.php';
?>

<style>
.categories-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.category-card {
    background: rgba(26, 26, 46, 0.8);
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid var(--sunset-orange);
    text-align: center;
    text-decoration: none;
    color: var(--sunset-light);
    transition: all 0.3s;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(255, 123, 37, 0.2);
    border-color: var(--sunset-red);
}

.category-icon {
    font-size: 2.5rem;
    color: var(--sunset-orange);
    margin-bottom: 1rem;
}

.category-card h3 {
    margin: 0.5rem 0;
    color: var(--sunset-light);
}

.product-count {
    color: var(--sunset-orange);
    font-size: 0.9rem;
    margin: 0;
}
</style>

<div class="categories-container">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Product Categories</h1>
        <a href="dashboard.php" class="btn btn-outline-light">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="categories-grid">
        <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <div class="category-icon">
                    <i class="<?= htmlspecialchars($category['icon']) ?>"></i>
                </div>
                <h3><?= htmlspecialchars($category['name']) ?></h3>
                <p class="product-count"><?= $category['product_count'] ?> Products</p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 