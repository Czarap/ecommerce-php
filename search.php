<?php
include 'includes/config.php';
$page_title = "Search Results";

// Initialize products array
$products = [];

if (isset($_GET['query']) && !empty($_GET['query'])) {
    $search_query = $conn->real_escape_string($_GET['query']);
    
    // Prepare the SQL query
    $stmt = $conn->prepare("SELECT * FROM products WHERE (name LIKE ? OR description LIKE ?) AND status = 'active'");
    $search_param = "%$search_query%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="search-results-container">
    <h1 class="search-results-title">Search Results for "<?= htmlspecialchars($_GET['query'] ?? '') ?>"</h1>
    
    <?php if (!empty($products)): ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php if ($product['image']): ?>
                        <img src="uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <div style="width: 100%; height: 200px; background: var(--sunset-darker); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-image" style="font-size: 3rem; color: var(--sunset-text); opacity: 0.3;"></i>
                        </div>
                    <?php endif; ?>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="product-price">₱<?= number_format($product['price'], 2) ?></p>
                        <p class="product-description"><?= substr(htmlspecialchars($product['description']), 0, 100) ?>...</p>
                        <a href="product.php?id=<?= $product['id'] ?>" class="btn">View Product</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-results">
            <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; color: var(--sunset-orange);"></i>
            <p>No products found matching your search.</p>
            <a href="shop.php" class="btn">Browse All Products</a>
        </div>
    <?php endif; ?>
</div>

<style>
/* Search Results Page - Dark Sunset Theme */
.search-results-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 15px;
}

.search-results-title {
    color: var(--sunset-orange);
    margin-bottom: 2rem;
    text-align: center;
    font-size: 2rem;
    text-shadow: 0 0 10px var(--sunset-glow);
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.product-card {
    background: rgba(26, 26, 46, 0.8);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid rgba(255, 123, 37, 0.2);
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(255, 123, 37, 0.2);
    border-color: var(--sunset-orange);
}

.product-card img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    transition: transform 0.3s;
}

.product-info {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.product-info h3 {
    color: var(--sunset-light);
    margin-bottom: 0.5rem;
    font-size: 1rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 2.8em;
}

.product-price {
    color: var(--sunset-orange);
    font-weight: bold;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

.product-description {
    color: var(--text-muted);
    margin-bottom: 1rem;
    font-size: 0.85rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 2.4em;
}

.no-results {
    text-align: center;
    padding: 3rem;
    background: rgba(26, 26, 46, 0.5);
    border-radius: 10px;
    border: 1px dashed var(--sunset-orange);
}

.no-results p {
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
    color: var(--sunset-light);
}

@media (max-width: 768px) {
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 1rem;
    }
    
    .search-results-title {
        font-size: 1.5rem;
    }
    
    .product-card img {
        height: 140px;
    }
    
    .product-info {
        padding: 0.75rem;
    }
    
    .product-info h3 {
        font-size: 0.9rem;
    }
    
    .product-price {
        font-size: 1rem;
    }
    
    .product-description {
        font-size: 0.8rem;
    }
    
    .btn {
        padding: 6px 12px;
        font-size: 0.8rem;
    }
}

.btn {
    display: inline-block;
    padding: 8px 16px;
    background: linear-gradient(135deg, var(--sunset-orange), var(--sunset-red));
    color: #fff;
    text-decoration: none;
    border: none;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    text-align: center;
    margin-top: auto;
}

.btn:hover {
    background: linear-gradient(135deg, var(--sunset-red), var(--sunset-orange));
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
    color: #fff;
}
</style>

<?php include 'includes/footer.php'; ?>