<?php
$page_title = "Shop";
include 'includes/config.php';
include 'includes/header.php';

// Initialize variables
$error = '';

try {
    // Get all categories with their products
    $sql = "SELECT 
                c.*, 
                p.*,
                p.id as product_id,
                c.id as category_id,
                c.name as category_name,
                p.name as product_name
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            WHERE c.status = 'active' 
            AND (p.status = 'active' OR p.status IS NULL)
            ORDER BY c.name ASC, p.name ASC";
    
    $result = $conn->query($sql);
    
    // Organize products by category
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categoryId = $row['category_id'];
        
        if (!isset($categories[$categoryId])) {
            $categories[$categoryId] = [
                'name' => $row['category_name'],
                'products' => []
            ];
        }
        
        if ($row['product_id']) {
            $categories[$categoryId]['products'][] = [
                'id' => $row['product_id'],
                'name' => $row['product_name'],
                'price' => $row['price'],
                'image' => $row['image'] ? 'uploads/products/' . $row['image'] : null,
                'description' => $row['description']
            ];
        }
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<style>
    :root {
            /* Dark Sunset Palette */
            --sunset-dark: #1a1a2e;
            --sunset-darker: #16213e;
            --sunset-orange: #ff7b25;
            --sunset-red: #ff2e63;
            --sunset-purple: #8a2be2;
            --sunset-text: #e6e6e6;
            --sunset-light: #f8a488;
            --sunset-glow: rgba(255, 123, 37, 0.7);
    }

.shop-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 20px;
    color: var(--sunset-text);
}

.category-section {
    margin-bottom: 3rem;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s ease forwards;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(145deg, var(--sunset-darker), var(--sunset-dark));
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid var(--sunset-orange);
    cursor: pointer;
    transition: all 0.3s ease;
}

.category-header:hover {
    transform: translateX(5px);
    border-color: var(--sunset-red);
}

.category-header h2 {
    color: var(--sunset-light);
    margin: 0;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.category-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.view-all-btn {
    color: var(--sunset-orange);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    padding: 0.5rem 1rem;
    border: 1px solid var(--sunset-orange);
    border-radius: 20px;
    background: rgba(255, 123, 37, 0.1);
}

.view-all-btn:hover {
    background: var(--sunset-orange);
    color: white;
    transform: translateX(5px);
}

.category-header .toggle-icon {
    transition: transform 0.3s ease;
}

.category-header.collapsed .toggle-icon {
    transform: rotate(-90deg);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    padding: 20px 0;
}

.product-card {
    background: var(--sunset-darker);
    border: 1px solid rgba(255, 123, 37, 0.2);
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
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
    transition: transform 0.3s ease;
}

.product-image:hover {
    transform: scale(1.05);
}

.placeholder-image {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--sunset-darker);
    color: var(--sunset-text);
    opacity: 0.5;
    gap: 10px;
}

.placeholder-image i {
    font-size: 3rem;
}

.placeholder-image span {
    font-size: 0.9rem;
}

@keyframes imageLoad {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.product-info {
    padding: 15px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.product-info h3 {
    margin: 0 0 10px;
    font-size: 1.1rem;
    color: var(--sunset-light);
}

.product-meta {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.price {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--sunset-orange);
}

.original-price {
    text-decoration: line-through;
    color: var(--sunset-text);
    opacity: 0.7;
    font-size: 0.9rem;
}

.discount {
    background: var(--sunset-red);
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
}

.product-actions {
    margin-top: auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.view-product-btn,
.add-to-cart-btn {
    padding: 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    transition: all 0.3s ease;
}

.view-product-btn {
    background: rgba(255, 123, 37, 0.1);
    color: var(--sunset-orange);
    border: 1px solid var(--sunset-orange);
}

.view-product-btn:hover {
    background: rgba(255, 123, 37, 0.2);
    transform: translateY(-2px);
}

.add-to-cart-btn {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
}

.add-to-cart-btn:hover {
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 77, 109, 0.3);
}

.cart-feedback {
    position: absolute;
    background: var(--sunset-green);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    animation: fadeIn 0.3s;
    z-index: 10;
    bottom: -30px;
    right: 0;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
    
    .product-actions {
        flex-direction: column;
    }
    
    .quantity-input {
        width: 100%;
    }
}

.category-filter {
    margin: 2rem 0;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.category-btn {
    padding: 0.5rem 1rem;
    background: var(--sunset-darker);
    border: 1px solid var(--sunset-orange);
    color: var(--sunset-text);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.category-btn:hover,
.category-btn.active {
    background: var(--sunset-orange);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
}

.category-btn i {
    font-size: 1.1rem;
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

/* Stagger animation for categories */
.category-section:nth-child(1) { animation-delay: 0.1s; }
.category-section:nth-child(2) { animation-delay: 0.2s; }
.category-section:nth-child(3) { animation-delay: 0.3s; }
.category-section:nth-child(4) { animation-delay: 0.4s; }
.category-section:nth-child(5) { animation-delay: 0.5s; }

.product-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: none;
    z-index: 1000;
}

.product-modal.active {
    display: block;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}

.modal-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--sunset-darker);
    border: 1px solid var(--sunset-orange);
    border-radius: 8px;
    width: 90%;
    max-width: 1000px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

/* Add to cart success toast */
.add-to-cart-success {
    position: fixed;
    top: 20px;
    right: 20px;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    padding: 15px 25px;
    border-radius: 4px;
    box-shadow: 0 4px 15px rgba(255, 123, 37, 0.3);
    display: flex;
    align-items: center;
    gap: 10px;
    transform: translateX(150%);
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
}

.add-to-cart-success.show {
    transform: translateX(0);
}

.add-to-cart-success i {
    font-size: 1.2rem;
}

.close-modal {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    color: var(--sunset-text);
    font-size: 1.5rem;
    cursor: pointer;
    z-index: 10;
    padding: 5px;
    transition: all 0.3s ease;
}

.close-modal:hover {
    color: var(--sunset-orange);
    transform: rotate(90deg);
}

.modal-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    padding: 30px;
}

.product-gallery {
    border-radius: 8px;
    overflow: hidden;
    opacity: 0;
    transform: translateX(-20px);
    transition: all 0.3s ease 0.2s;
}

.product-modal.active .product-gallery {
    opacity: 1;
    transform: translateX(0);
}

.modal-product-image {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 8px;
    transition: transform 0.3s ease;
}

.modal-product-image:hover {
    transform: scale(1.05);
}

.product-details {
    opacity: 0;
    transform: translateX(20px);
    transition: all 0.3s ease 0.3s;
}

.product-modal.active .product-details {
    opacity: 1;
    transform: translateX(0);
}

.modal-product-name {
    color: var(--sunset-light);
    font-size: 2rem;
    margin-bottom: 1rem;
}

.modal-product-price {
    font-size: 1.5rem;
    color: var(--sunset-orange);
    margin-bottom: 1rem;
}

.modal-product-description {
    color: var(--sunset-text);
    line-height: 1.6;
    margin-bottom: 2rem;
}

.quantity-wrapper {
    margin-bottom: 2rem;
}

.quantity-wrapper label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--sunset-text);
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.qty-btn {
    background: var(--sunset-darker);
    border: 1px solid var(--sunset-orange);
    color: var(--sunset-text);
    width: 40px;
    height: 40px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.qty-btn:hover {
    background: var(--sunset-orange);
    color: white;
}

.modal-add-to-cart {
    width: 100%;
    padding: 15px;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.modal-add-to-cart:hover {
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 77, 109, 0.3);
}

.view-full-details {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: var(--sunset-text);
    text-decoration: none;
    padding: 15px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 123, 37, 0.2);
    border-radius: 8px;
    background: rgba(255, 123, 37, 0.05);
}

.view-full-details::before {
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
    transition: left 0.5s ease;
}

.view-full-details:hover::before {
    left: 100%;
}

.view-full-details i {
    transition: transform 0.3s ease;
}

.view-full-details:hover {
    color: var(--sunset-orange);
    border-color: var(--sunset-orange);
    box-shadow: 0 0 15px rgba(255, 123, 37, 0.2);
    transform: translateY(-2px);
}

.view-full-details:hover i {
    transform: translateX(5px);
}

.view-full-details .text {
    position: relative;
}

.view-full-details .text::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 1px;
    background: var(--sunset-orange);
    transition: width 0.3s ease;
}

.view-full-details:hover .text::after {
    width: 100%;
}

@media (max-width: 768px) {
    .modal-content {
        grid-template-columns: 1fr;
    }
    
    .modal-product-image {
        height: 300px;
    }
}

/* Add to cart animations */
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

/* Toast notifications */
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

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
    
    .product-actions {
        flex-direction: column;
    }
    
    .quantity-input {
        width: 100%;
    }
}
</style>

<div class="shop-container">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php else: ?>
        <?php foreach ($categories as $categoryId => $category): ?>
            <section class="category-section" id="category-<?php echo $categoryId; ?>">
                <div class="category-header" onclick="toggleCategory(<?php echo $categoryId; ?>)">
                    <h2>
                        <?php
                        // Get appropriate icon for each category
                        $categoryIcon = '';
                        switch(strtolower($category['name'])) {
                            case 'electronics':
                                $categoryIcon = 'bi-laptop';
                                break;
                            case 'clothing':
                                $categoryIcon = 'bi-bag';
                                break;
                            case 'books':
                                $categoryIcon = 'bi-book';
                                break;
                            case 'beauty':
                                $categoryIcon = 'bi-gem';
                                break;
                            case 'automotive':
                                $categoryIcon = 'bi-car-front';
                                break;
                            default:
                                $categoryIcon = 'bi-grid';
                        }
                        ?>
                        <i class="bi <?php echo $categoryIcon; ?>"></i>
                        <?php echo htmlspecialchars($category['name']); ?>
                        <span class="badge bg-sunset-orange"><?php echo count($category['products']); ?></span>
                    </h2>
                    <div class="category-actions">
                        <?php if (count($category['products']) > 3): ?>
                            <a href="category.php?id=<?php echo $categoryId; ?>" class="view-all-btn">
                                View All <i class="bi bi-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                        <i class="bi bi-chevron-down toggle-icon"></i>
                    </div>
                </div>
                
                <div class="products-grid" id="products-<?php echo $categoryId; ?>">
                    <?php if (empty($category['products'])): ?>
                        <div class="no-products">
                            <p>No products available in this category.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($category['products'] as $product): ?>
                            <div class="product-card">
                                <?php if ($product['image'] && file_exists($product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-image"
                                         loading="lazy"
                                         onerror="this.onerror=null; this.src='assets/images/no-image.svg';">
                                <?php else: ?>
                                    <div class="product-image placeholder-image">
                                        <i class="fas fa-image"></i>
                                        <span>No image available</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="product-meta">
                                        <span class="price">₱<?php echo number_format($product['price'], 2); ?></span>
                                    </div>
                                    <div class="product-actions">
                                        <button class="view-product-btn" 
                                                onclick="viewProduct(<?php echo htmlspecialchars(json_encode([
                                                    'id' => $product['id'],
                                                    'name' => $product['name'],
                                                    'price' => $product['price'],
                                                    'image' => $product['image'],
                                                    'description' => $product['description']
                                                ])); ?>)">
                                            <i class="fas fa-eye"></i> Quick View
                                        </button>
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <button class="add-to-cart-btn" 
                                                    onclick="addToCart(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <a href="login.php" class="add-to-cart-btn">
                                                <i class="fas fa-sign-in-alt"></i> Login to Buy
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add success message container -->
<div class="add-to-cart-success" style="display: none;">
    <i class="bi bi-check-circle"></i>
    <span>Item added to cart</span>
</div>

<div class="product-modal" id="productModal">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <button class="close-modal">
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="modal-content">
            <div class="product-gallery">
                <img src="" alt="" class="modal-product-image">
            </div>
            <div class="product-details">
                <h2 class="modal-product-name"></h2>
                <div class="modal-product-price"></div>
                <p class="modal-product-description"></p>
                <div class="quantity-wrapper">
                    <label>Quantity:</label>
                    <div class="quantity-controls">
                        <button class="qty-btn minus">-</button>
                        <input type="number" class="quantity-input" value="1" min="1">
                        <button class="qty-btn plus">+</button>
                    </div>
                </div>
                <button class="modal-add-to-cart">
                    <i class="bi bi-cart-plus"></i>
                    Add to Cart
                </button>
                <a href="#" class="view-full-details">
                    <i class="bi bi-arrow-right"></i>
                    View Full Details
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Minimal animation for category toggling
function toggleCategory(categoryId) {
    const header = document.querySelector(`#category-${categoryId} .category-header`);
    const products = document.querySelector(`#products-${categoryId}`);
    const icon = header.querySelector('.toggle-icon');
    
    header.classList.toggle('collapsed');
    
    if (header.classList.contains('collapsed')) {
        products.style.display = 'none';
        icon.style.transform = 'rotate(-90deg)';
    } else {
        products.style.display = 'grid';
        icon.style.transform = 'rotate(0deg)';
    }
}

// Toast notification function
function showToast(message, type = 'success') {
    // Remove any existing toasts
    document.querySelectorAll('.toast-notification').forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
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

// Product quick view functionality
function viewProduct(product) {
    const modal = document.getElementById('productModal');
    const image = modal.querySelector('.modal-product-image');
    const name = modal.querySelector('.modal-product-name');
    const price = modal.querySelector('.modal-product-price');
    const description = modal.querySelector('.modal-product-description');
    const fullDetailsLink = modal.querySelector('.view-full-details');
    const addToCartBtn = modal.querySelector('.modal-add-to-cart');
    
    // Update modal content
    image.src = product.image || 'assets/images/no-image.svg';
    image.alt = product.name;
    name.textContent = product.name;
    price.textContent = `$${parseFloat(product.price).toFixed(2)}`;
    description.textContent = product.description || 'No description available';
    fullDetailsLink.href = `product.php?id=${product.id}`;
    addToCartBtn.dataset.productId = product.id;
    
    // Show modal
    modal.classList.add('active');
    
    // Handle modal close
    const closeModal = () => {
        modal.classList.remove('active');
    };
    
    // Close modal when clicking overlay or close button
    modal.querySelector('.modal-overlay').addEventListener('click', closeModal);
    modal.querySelector('.close-modal').addEventListener('click', closeModal);
    
    // Prevent closing when clicking modal content
    modal.querySelector('.modal-container').addEventListener('click', (e) => {
        e.stopPropagation();
    });
    
    // Handle quantity controls
    const quantityInput = modal.querySelector('.quantity-input');
    const minusBtn = modal.querySelector('.qty-btn.minus');
    const plusBtn = modal.querySelector('.qty-btn.plus');
    
    minusBtn.addEventListener('click', () => {
        const currentValue = parseInt(quantityInput.value);
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    });
    
    plusBtn.addEventListener('click', () => {
        const currentValue = parseInt(quantityInput.value);
        if (currentValue < 99) {
            quantityInput.value = currentValue + 1;
        }
    });
    
    // Reset quantity when opening modal
    quantityInput.value = 1;
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
            
            // Close modal if product was added from modal
            const modal = document.getElementById('productModal');
            if (modal.classList.contains('active')) {
                modal.classList.remove('active');
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

// Initialize all categories as expanded
document.addEventListener('DOMContentLoaded', function() {
    const categories = document.querySelectorAll('.category-section');
    categories.forEach(category => {
        const categoryId = category.id.replace('category-', '');
        const products = document.querySelector(`#products-${categoryId}`);
        products.style.display = 'grid';
    });

    // Add to cart from modal
    const modal = document.getElementById('productModal');
    if (modal) {
        const addToCartBtn = modal.querySelector('.modal-add-to-cart');
        const quantityInput = modal.querySelector('.quantity-input');
        
        addToCartBtn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const quantity = parseInt(quantityInput.value) || 1;
            addToCart(productId, quantity);
        });
    }
});
</script>
</rewritten_file>