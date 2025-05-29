<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'includes/config.php';

$page_title = "Category Products";

// Initialize variables
$error = '';
$category = null;
$products = [];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error = "Invalid category ID";
} else {
    $category_id = intval($_GET['id']);
    
    try {
        // Get category details
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $category = $stmt->get_result()->fetch_assoc();
        
        if ($category) {
            // Get all products in this category
            $stmt = $conn->prepare("
                SELECT * FROM products 
                WHERE category_id = ? 
                AND status = 'active' 
                ORDER BY name ASC
            ");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "Category not found";
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="category-container">
    <?php if ($error): ?>
        <div class="error-message">
            <h2>Error</h2>
            <p><?= htmlspecialchars($error) ?></p>
            <a href="shop.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Shop
            </a>
        </div>
    <?php elseif ($category): ?>
        <div class="category-header">
            <h1><?= htmlspecialchars($category['name']) ?></h1>
            <p><?= count($products) ?> products found</p>
        </div>
        
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php if ($product['image']): ?>
                        <img src="uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="product-image"
                             loading="lazy">
                    <?php else: ?>
                        <div class="product-image placeholder-image">
                            <i class="fas fa-image"></i>
                            <span>No image available</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <div class="price">₱<?= number_format($product['price'], 2) ?></div>
                        <div class="product-actions">
                            <a href="product.php?id=<?= $product['id'] ?>" class="btn view-btn">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <button onclick="addToCart(<?= $product['id'] ?>)" class="btn add-cart-btn">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
:root {
    --sunset-dark: #1a1a2e;
    --sunset-darker: #16213e;
    --sunset-orange: #ff7b25;
    --sunset-red: #ff2e63;
    --sunset-purple: #8a2be2;
    --sunset-text: #f0e6d2;
    --sunset-light: #f8a488;
    --sunset-brown: #8B4513;
    --sunset-glow: rgba(255, 123, 37, 0.7);
}

.category-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 20px;
    color: var(--sunset-text);
}

.category-header {
    background: linear-gradient(145deg, var(--sunset-darker), var(--sunset-dark));
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border: 1px solid var(--sunset-orange);
    text-align: center;
    animation: fadeIn 0.5s ease;
}

.category-header h1 {
    color: var(--sunset-light);
    margin-bottom: 1rem;
    font-size: 2.5rem;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    padding: 1rem;
    animation: fadeInUp 0.5s ease;
}

.product-card {
    background: linear-gradient(145deg, var(--sunset-darker), var(--sunset-dark));
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid rgba(255, 123, 37, 0.2);
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(255, 123, 37, 0.3);
    border-color: var(--sunset-orange);
}

.product-image {
    width: 100%;
    height: 280px;
    object-fit: cover;
    border-bottom: 1px solid rgba(255, 123, 37, 0.2);
}

.product-info {
    padding: 1.5rem;
}

.product-info h3 {
    color: var(--sunset-light);
    margin-bottom: 0.5rem;
    font-size: 1.2rem;
}

.price {
    color: var(--sunset-orange);
    font-size: 1.3rem;
    font-weight: bold;
    margin: 1rem 0;
}

.product-actions {
    display: flex;
    gap: 10px;
    margin-top: 1rem;
}

.btn {
    flex: 1;
    padding: 10px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.view-btn {
    background: rgba(138, 43, 226, 0.2);
    color: white;
    border: 1px solid var(--sunset-purple);
}

.view-btn:hover {
    background: var(--sunset-purple);
    transform: translateY(-2px);
}

.add-cart-btn {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
}

.add-cart-btn:hover {
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
    transform: translateY(-2px);
}

.error-message {
    text-align: center;
    padding: 2rem;
    background: rgba(255, 46, 99, 0.1);
    border: 1px solid var(--sunset-red);
    border-radius: 8px;
    margin: 2rem auto;
    max-width: 600px;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    text-decoration: none;
    border-radius: 4px;
    margin-top: 1rem;
    transition: all 0.3s ease;
}

.back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 77, 109, 0.3);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
    
    .category-header h1 {
        font-size: 2rem;
    }
}

.flying-dot {
    position: fixed;
    width: 10px;
    height: 10px;
    background: var(--sunset-orange);
    border-radius: 50%;
    pointer-events: none;
    z-index: 9999;
    transform: translate(0, 0);
}

@keyframes flyToCart {
    0% {
        transform: translate(0, 0) scale(1);
    }
    75% {
        transform: translate(var(--tx), var(--ty)) scale(0.8);
        opacity: 1;
    }
    100% {
        transform: translate(var(--tx), var(--ty)) scale(0);
        opacity: 0;
    }
}

.cart-bump {
    animation: cartBump 0.3s cubic-bezier(0.36, 0, 0.66, -0.56);
}

@keyframes cartBump {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.toast-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 12px 24px;
    border-radius: 8px;
    display: flex;
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
</style>

<script>
// Create flying dot animation
function createFlyingDot(startElement, endElement) {
    // Remove any existing flying dots
    document.querySelectorAll('.flying-dot').forEach(dot => dot.remove());
    
    const dot = document.createElement('div');
    dot.className = 'flying-dot';
    
    const start = startElement.getBoundingClientRect();
    const end = endElement.getBoundingClientRect();
    
    // Position dot at the start element's center
    dot.style.left = start.left + start.width / 2 + 'px';
    dot.style.top = start.top + start.height / 2 + 'px';
    
    // Calculate translation
    const tx = end.left + end.width / 2 - (start.left + start.width / 2);
    const ty = end.top + end.height / 2 - (start.top + start.height / 2);
    
    document.body.appendChild(dot);
    
    // Force reflow
    void dot.offsetWidth;
    
    // Start animation
    requestAnimationFrame(() => {
        dot.style.setProperty('--tx', `${tx}px`);
        dot.style.setProperty('--ty', `${ty}px`);
        dot.style.animation = 'flyToCart 0.6s ease-out forwards';
    });
    
    // Remove dot after animation
    setTimeout(() => dot.remove(), 600);
}

// Add to cart functionality
async function addToCart(productId, quantity = 1) {
    const submitBtn = event.currentTarget;
    if (submitBtn.disabled) return;
    
    const originalText = submitBtn.innerHTML;
    
    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

        const response = await fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        // Reset button immediately
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;

        if (data.status === 'success') {
            // Update cart count
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
                cartCount.classList.remove('cart-bump');
                void cartCount.offsetWidth;
                cartCount.classList.add('cart-bump');
            }

            // Create flying dot animation
            const cartIcon = document.querySelector('.fas.fa-shopping-cart');
            if (cartIcon && submitBtn) {
                createFlyingDot(submitBtn, cartIcon);
            }

            showToast('Added to cart successfully!', 'success');
        } else {
            if (data.message === 'Please login first') {
                window.location.href = 'login.php';
            } else {
                showToast(data.message || 'Failed to add to cart', 'error');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to add to cart. Please try again.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Toast notification function
function showToast(message, type = 'success') {
    // Remove any existing toasts
    document.querySelectorAll('.toast-notification').forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // Force reflow
    void toast.offsetWidth;
    
    // Show toast
    requestAnimationFrame(() => {
        toast.classList.add('show');
    });
    
    // Hide and remove toast after delay
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>