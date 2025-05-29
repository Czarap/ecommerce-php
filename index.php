<?php 
$page_title = "Home";
include 'includes/config.php';
include 'includes/header.php';

// Get featured products
$featured_products = [];
try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE featured = 1 AND status = 'active' LIMIT 4");
    $stmt->execute();
    $featured_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Error loading featured products: " . $e->getMessage();
}
?>

<style>
/* Hero Section */
.hero {
    background: linear-gradient(rgba(26, 26, 46, 0.8), rgba(26, 26, 46, 0.8)), 
                url('images/hero.jpg') center/cover;
    color: var(--sunset-light);
    text-align: center;
    padding: 120px 20px;
    margin-bottom: 40px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}

.hero h2 {
    font-size: 2.5rem;
    margin-bottom: 15px;
    text-shadow: 0 0 10px var(--sunset-glow);
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.8s ease forwards;
}

.hero p {
    font-size: 1.2rem;
    margin-bottom: 35px;
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.8s ease forwards 0.3s;
}

.hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 123, 37, 0.2),
        transparent
    );
    animation: shine 3s infinite;
}

.shop-now-btn {
    display: inline-block;
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    color: white;
    background: linear-gradient(45deg, var(--sunset-orange), var(--sunset-red));
    border-radius: 30px;
    border: none;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.8s ease forwards 0.6s;
    box-shadow: 0 4px 15px rgba(255, 123, 37, 0.3);
}

.shop-now-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        120deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    transition: all 0.6s ease;
}

.shop-now-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(255, 123, 37, 0.5);
    color: white;
    background: linear-gradient(45deg, var(--sunset-red), var(--sunset-purple));
}

.shop-now-btn:hover::before {
    left: 100%;
}

.shop-now-btn:active {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(255, 123, 37, 0.4);
}

.shop-now-btn i {
    margin-left: 8px;
    transition: transform 0.3s ease;
}

.shop-now-btn:hover i {
    transform: translateX(5px);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes shine {
    0% {
        left: -100%;
    }
    20% {
        left: 100%;
    }
    100% {
        left: 100%;
    }
}

/* Featured Products Section */
.featured-section {
    max-width: 1200px;
    margin: 3rem auto;
    padding: 0 20px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--sunset-orange);
    opacity: 0;
    animation: fadeIn 0.8s ease forwards;
}

.section-header h2 {
    color: var(--sunset-light);
    font-size: 1.8rem;
    margin: 0;
    position: relative;
    padding-left: 15px;
}

.section-header h2::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 100%;
    background: var(--sunset-orange);
    border-radius: 2px;
}

.view-all {
    color: var(--sunset-orange);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    padding: 8px 15px;
    border: 1px solid var(--sunset-orange);
    border-radius: 20px;
    font-size: 0.9rem;
}

.view-all:hover {
    color: white;
    background: var(--sunset-orange);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
}

.featured-products {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 2rem;
}

.product-card {
    background: linear-gradient(145deg, var(--sunset-darker), var(--sunset-dark));
    border-radius: 15px;
    overflow: hidden;
    border: 1px solid rgba(255, 123, 37, 0.2);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.6s ease forwards;
}

.product-card:nth-child(1) { animation-delay: 0.1s; }
.product-card:nth-child(2) { animation-delay: 0.2s; }
.product-card:nth-child(3) { animation-delay: 0.3s; }
.product-card:nth-child(4) { animation-delay: 0.4s; }

.product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(255, 123, 37, 0.3);
    border-color: var(--sunset-orange);
}

.product-image {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-bottom: 1px solid rgba(255, 123, 37, 0.2);
    transition: transform 0.6s ease;
}

.product-card:hover .product-image {
    transform: scale(1.08);
}

.product-info {
    padding: 20px;
    background: linear-gradient(0deg, var(--sunset-darker) 0%, transparent 100%);
}

.product-info h3 {
    color: var(--sunset-light);
    margin: 0 0 10px 0;
    font-size: 1.2rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color 0.3s ease;
}

.product-card:hover .product-info h3 {
    color: var(--sunset-orange);
}

.product-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.price {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--sunset-orange);
    transition: transform 0.3s ease;
}

.product-card:hover .price {
    transform: scale(1.1);
}

.product-actions {
    display: flex;
    gap: 10px;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
}

.product-card:hover .product-actions {
    opacity: 1;
    transform: translateY(0);
}

.view-btn {
    flex: 1;
    padding: 10px;
    background: rgba(138, 43, 226, 0.2);
    color: white;
    border: 1px solid var(--sunset-purple);
    border-radius: 8px;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.view-btn:hover {
    background: var(--sunset-purple);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(138, 43, 226, 0.3);
}

.add-to-cart-btn {
    padding: 10px 15px;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.add-to-cart-btn:hover {
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 77, 109, 0.3);
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .featured-products {
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
    }

    .product-image {
        height: 200px;
    }

    .product-info {
        padding: 15px;
    }

    .product-info h3 {
        font-size: 1.1rem;
    }

    .price {
        font-size: 1.2rem;
    }

    .product-actions {
        flex-direction: column;
    }

    .view-btn, .add-to-cart-btn {
        width: 100%;
        padding: 8px;
    }

    .hero {
        padding: 80px 20px;
    }

    .hero h2 {
        font-size: 2rem;
    }

    .hero p {
        font-size: 1rem;
        margin-bottom: 25px;
    }

    .shop-now-btn {
        padding: 12px 30px;
        font-size: 1rem;
    }
}

/* Toast notification styles */
.toast-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 12px 24px;
    border-radius: 8px;
    display: none;
    align-items: center;
    gap: 8px;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.toast-notification.success {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
}

.toast-notification.error {
    background: linear-gradient(to right, #dc3545, #c82333);
    color: white;
}

.toast-notification i {
    font-size: 1.2em;
}

.toast-notification.show {
    transform: translateY(0);
    opacity: 1;
}

.toast-notification.hide {
    transform: translateY(100px);
    opacity: 0;
}

/* Cart count animation */
.cart-bump {
    animation: cartBump 0.3s cubic-bezier(0.36, 0, 0.66, -0.56);
}

@keyframes cartBump {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}
</style>

<div class="hero">
    <h2>Welcome to Our Store</h2>
    <p>Discover Amazing Products</p>
    <a href="shop.php" class="shop-now-btn">
        Shop Now <i class="fas fa-arrow-right"></i>
    </a>
</div>

<div class="featured-section">
    <div class="section-header">
        <h2>Featured Products</h2>
        <a href="shop.php" class="view-all">View All Products →</a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="featured-products">
        <?php foreach ($featured_products as $product): ?>
            <div class="product-card">
                <?php if ($product['image']): ?>
                    <img src="uploads/products/<?= htmlspecialchars($product['image']) ?>" class="product-image" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php else: ?>
                    <div class="product-image" style="background: var(--sunset-darker); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-image" style="font-size: 3rem; color: var(--sunset-text); opacity: 0.3;"></i>
                    </div>
                <?php endif; ?>
                <div class="product-info">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <div class="product-meta">
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                    </div>
                    <div class="product-actions">
                        <a href="product.php?id=<?= $product['id'] ?>" class="view-btn">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <button class="add-to-cart-btn" data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add toast notification container -->
<div class="toast-notification" style="display: none;">
    <i class="bi bi-check-circle"></i>
    <span></span>
</div>

<script>
// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.querySelector('.toast-notification');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Show toast
    toast.style.display = 'flex';
    requestAnimationFrame(() => {
        toast.classList.add('show');
    });
    
    // Hide toast after delay
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => {
            toast.style.display = 'none';
            toast.classList.remove('show', 'hide');
        }, 300);
    }, 3000);
}

// Add to cart functionality
async function addToCart(productId, quantity = 1) {
    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        const response = await fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.status === 'success') {
            // Show success toast
            showToast('Item added to cart successfully', 'success');
            
            // Update cart count
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
                cartCount.classList.remove('cart-bump');
                void cartCount.offsetWidth; // Trigger reflow
                cartCount.classList.add('cart-bump');
            }
        } else {
            throw new Error(data.message || 'Failed to add to cart');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast(error.message === 'The operation was aborted.' 
            ? 'Request timed out. Please try again.' 
            : error.message || 'Failed to add to cart. Please try again.', 'error');
    }
}

// Initialize add to cart buttons
document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            addToCart(productId);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>