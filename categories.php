<?php
include 'includes/config.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$page_title = "Product Categories";
include 'includes/header.php';

// Get all categories with product counts - using the same query structure as header.php
$stmt = $conn->prepare("
    SELECT c.*, 
           (
               SELECT COUNT(DISTINCT p.id) 
               FROM products p 
               WHERE p.category_id = c.id 
               AND p.status = 'active'
           ) as product_count
    FROM categories c 
    WHERE c.status = 'active' 
    ORDER BY c.name ASC
");
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Ensure product_count is set for each category
foreach ($categories as &$category) {
    $category['product_count'] = intval($category['product_count'] ?? 0);
}
unset($category); // Break the reference
?>

<div class="categories-container">
    <h1 class="categories-title">Product Categories</h1>
    <div class="categories-grid">
        <?php foreach ($categories as $category): ?>
            <a href="category.php?id=<?= $category['id'] ?>" class="category-card">
                <div class="category-icon">
                    <i class="bi <?= htmlspecialchars($category['icon']) ?>"></i>
                </div>
                <h3><?= htmlspecialchars($category['name']) ?></h3>
                <div class="product-count"><?= $category['product_count'] ?> Products</div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<style>
.categories-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 20px;
}

.categories-title {
    text-align: center;
    color: var(--sunset-orange);
    font-size: 2rem;
    margin-bottom: 2rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.category-card {
    background: rgba(26, 26, 46, 0.8);
    border: 1px solid rgba(255, 123, 37, 0.2);
    border-radius: 10px;
    padding: 2rem;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.category-card:hover {
    transform: translateY(-5px);
    border-color: var(--sunset-orange);
    box-shadow: 0 5px 15px rgba(255, 123, 37, 0.2);
}

.category-icon {
    font-size: 2.5rem;
    color: var(--sunset-orange);
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.category-card:hover .category-icon {
    transform: scale(1.1);
    color: var(--sunset-pink);
}

.category-card h3 {
    color: var(--text-light);
    font-size: 1.2rem;
    margin: 0;
}

.product-count {
    color: var(--text-muted);
    font-size: 0.9rem;
    background: rgba(255, 123, 37, 0.1);
    padding: 0.25rem 1rem;
    border-radius: 20px;
    transition: all 0.3s ease;
}

.category-card:hover .product-count {
    background: var(--sunset-orange);
    color: var(--text-light);
}

@media (max-width: 768px) {
    .categories-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }

    .category-card {
        padding: 1.5rem;
    }

    .category-icon {
        font-size: 2rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>