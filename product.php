<?php 
session_start();
$page_title = "Product Details";
include 'includes/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize product variable
$product = null;
$error_message = '';

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // Debug info
    error_log("Fetching product ID: " . $product_id);
    
    // First verify the products table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'products'");
    if($table_check->num_rows === 0) {
        $error_message = "Products table does not exist";
        error_log($error_message);
    } else {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        if (!$stmt) {
            $error_message = "Database error: " . $conn->error;
            error_log($error_message);
        } else {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result && $result->num_rows > 0) {
                $product = $result->fetch_assoc();
                $page_title = htmlspecialchars($product['name']) . " - E-Czar";
                error_log("Product found: " . json_encode($product));
            } else {
                $error_message = "Product not found";
                error_log("Product not found for ID: " . $product_id);
            }
        }
    }
} else {
    $error_message = "Invalid product ID";
    error_log("Invalid product ID in request: " . print_r($_GET, true));
}

include 'includes/header.php';
?>

<style>
/* Dark Sunset Theme */
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

body {
    background: linear-gradient(135deg, var(--sunset-dark), var(--sunset-darker));
    color: var(--sunset-text);
    min-height: 100vh;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.6s ease forwards;
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

.error-message {
    background: rgba(220, 53, 69, 0.1);
    color: #ff4d6d;
    padding: 20px;
    border-radius: 8px;
    margin: 40px auto;
    text-align: center;
    border: 1px solid rgba(220, 53, 69, 0.3);
    max-width: 600px;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.error-message h2 {
    color: var(--sunset-red);
    margin-bottom: 10px;
}

.error-message .back-btn {
    display: inline-block;
    margin-top: 15px;
    padding: 10px 20px;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.error-message .back-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.error-message .back-btn:hover::before {
    left: 100%;
}

.error-message .back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 77, 109, 0.3);
}

.product-detail {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin: 40px 0;
    background: rgba(40, 30, 45, 0.8);
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(139, 69, 19, 0.3);
    opacity: 0;
    animation: fadeIn 0.8s ease forwards 0.3s;
    position: relative;
    overflow: hidden;
}

.product-detail::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, 
        transparent 0%,
        rgba(255, 123, 37, 0.1) 50%,
        transparent 100%
    );
    transform: translateX(-100%);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.product-images {
    opacity: 0;
    transform: translateX(-20px);
    animation: slideInLeft 0.8s ease forwards 0.5s;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.product-images img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--sunset-brown);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-images img:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(139, 69, 19, 0.4);
}

.product-info {
    opacity: 0;
    transform: translateX(20px);
    animation: slideInRight 0.8s ease forwards 0.7s;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.product-info h1 {
    color: var(--sunset-light);
    margin-bottom: 15px;
    font-size: 2.2rem;
    position: relative;
    display: inline-block;
}

.product-info h1::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    animation: lineGrow 0.8s ease forwards 1s;
}

@keyframes lineGrow {
    to { width: 100%; }
}

.price {
    font-size: 1.8rem;
    color: var(--sunset-orange);
    margin: 15px 0;
    font-weight: bold;
    text-shadow: 0 0 10px rgba(255, 123, 37, 0.3);
    animation: pulsate 2s infinite;
}

@keyframes pulsate {
    0% { text-shadow: 0 0 10px rgba(255, 123, 37, 0.3); }
    50% { text-shadow: 0 0 20px rgba(255, 123, 37, 0.5); }
    100% { text-shadow: 0 0 10px rgba(255, 123, 37, 0.3); }
}

.product-info p {
    line-height: 1.6;
    margin-bottom: 20px;
    opacity: 0;
    animation: fadeIn 0.8s ease forwards 0.9s;
}

.quantity-selector {
    margin: 25px 0;
    opacity: 0;
    animation: fadeIn 0.8s ease forwards 1.1s;
}

.quantity-selector label {
    display: block;
    margin-bottom: 8px;
    color: var(--sunset-light);
    transform: translateY(10px);
    animation: slideDown 0.5s ease forwards 1.2s;
}

@keyframes slideDown {
    to {
        transform: translateY(0);
    }
}

/* Quantity input styling */
.quantity-selector input[type="number"] {
    -webkit-appearance: none;
    -moz-appearance: textfield;
    appearance: none;
    width: 80px;
    padding: 10px 15px;
    background: rgba(30, 20, 35, 0.8);
    border: 1px solid var(--sunset-brown);
    border-radius: 4px;
    color: var(--sunset-text);
    font-size: 1rem;
    text-align: center;
    transition: all 0.3s ease;
}

/* Remove spinner buttons for Chrome, Safari, Edge, Opera */
.quantity-selector input[type="number"]::-webkit-outer-spin-button,
.quantity-selector input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Remove spinner buttons for Firefox */
.quantity-selector input[type="number"] {
    -moz-appearance: textfield;
}

.quantity-selector input[type="number"]:focus {
    outline: none;
    border-color: var(--sunset-orange);
    box-shadow: 0 0 10px rgba(255, 123, 37, 0.3);
}

.btn {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(to right, var(--sunset-brown), var(--sunset-red));
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    position: relative;
    overflow: hidden;
    opacity: 0;
    animation: fadeIn 0.8s ease forwards 1.3s;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn:hover::before {
    left: 100%;
}

.btn:hover {
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-brown));
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(205, 92, 92, 0.3);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Add to cart success animation */
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
    opacity: 0;
    visibility: hidden;
}

.add-to-cart-success.show {
    transform: translateX(0);
    opacity: 1;
    visibility: visible;
}

.add-to-cart-success i {
    font-size: 1.2rem;
    animation: successIconPop 0.5s ease;
}

@keyframes successIconPop {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Responsive */
@media (max-width: 768px) {
    .product-detail {
        grid-template-columns: 1fr;
    }
    
    .product-images img {
        height: 300px;
    }
    
    .product-info h1 {
        font-size: 1.8rem;
    }
    
    .price {
        font-size: 1.5rem;
    }
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

.qty-btn {
    width: 36px;
    height: 36px;
    border: 1px solid var(--sunset-brown);
    background: rgba(30, 20, 35, 0.8);
    color: var(--sunset-text);
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:hover {
    background: var(--sunset-orange);
    border-color: var(--sunset-orange);
    transform: translateY(-2px);
}

/* Cart Animation */
@keyframes cartBump {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.cart-bump {
    animation: cartBump 0.3s ease-in-out;
}

/* Flying dot animation */
@keyframes flyToCart {
    0% {
        transform: scale(0.5) translate(0, 0);
        opacity: 1;
    }
    100% {
        transform: scale(0.1) translate(var(--tx), var(--ty));
        opacity: 0;
    }
}

.flying-dot {
    position: fixed;
    width: 15px;
    height: 15px;
    background: var(--sunset-orange);
    border-radius: 50%;
    pointer-events: none;
    z-index: 9999;
}

/* Login prompt styling */
.login-prompt {
    background: rgba(255, 46, 99, 0.1);
    border: 1px solid var(--sunset-red);
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
    text-align: center;
}

.login-link {
    color: var(--sunset-orange);
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
}

.login-link:hover {
    color: var(--sunset-red);
    text-shadow: 0 0 10px var(--sunset-glow);
}

/* Back button styles */
.back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: var(--sunset-text);
    text-decoration: none;
    border-radius: 4px;
    margin: 20px 0;
    transition: all 0.3s ease;
    font-weight: 500;
    border: none;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.back-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 123, 37, 0.3);
}

.back-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.back-button:hover::before {
    left: 100%;
}

.back-button i {
    font-size: 1.1em;
    transition: transform 0.3s ease;
}

.back-button:hover i {
    transform: translateX(-3px);
}
</style>

<div class="container">
    <!-- Add back button at the top -->
    <a href="javascript:history.back()" class="back-button">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    
    <?php if ($error_message): ?>
        <div class="error-message">
            <h2><i class="fas fa-exclamation-circle"></i> Oops!</h2>
            <p><?= htmlspecialchars($error_message) ?></p>
            <a href="shop.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Shop
            </a>
        </div>
    <?php elseif ($product): ?>
        <div class="product-detail">
            <div class="product-images">
                <?php if ($product['image']): ?>
                    <img src="uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         loading="lazy"
                         onerror="this.onerror=null; this.src='assets/images/no-image.svg';">
                <?php else: ?>
                    <div class="placeholder-image">
                        <i class="fas fa-image"></i>
                        <span>No image available</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="product-info">
                <h1><?= htmlspecialchars($product['name']) ?></h1>
                <p class="price">₱<?= number_format($product['price'], 2) ?></p>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form id="addToCartForm" action="add_to_cart.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <div class="quantity-selector">
                            <label for="quantity">Quantity:</label>
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn minus">-</button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="99">
                                <button type="button" class="qty-btn plus">+</button>
                            </div>
                        </div>
                        <button type="submit" class="btn add-to-cart">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </form>
                <?php else: ?>
                    <div class="login-prompt">
                        <p>Please <a href="login.php" class="login-link">login</a> to add items to your cart.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="add-to-cart-success">
    <i class="fas fa-check-circle"></i>
    <span>Added to cart successfully!</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addToCartForm = document.getElementById('addToCartForm');
    const quantityInput = document.getElementById('quantity');
    const minusBtn = document.querySelector('.qty-btn.minus');
    const plusBtn = document.querySelector('.qty-btn.plus');
    let isSubmitting = false;
    
    // Quantity controls
    if (minusBtn && plusBtn && quantityInput) {
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

        // Ensure valid quantity input
        quantityInput.addEventListener('change', () => {
            let value = parseInt(quantityInput.value);
            if (isNaN(value) || value < 1) value = 1;
            if (value > 99) value = 99;
            quantityInput.value = value;
        });
    }
    
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (isSubmitting) {
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!submitBtn || submitBtn.disabled) return;
            
            const originalBtnText = submitBtn.innerHTML;
            
            try {
                isSubmitting = true;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                
                const formData = new FormData(this);
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Reset states immediately after getting response
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                if (data.status === 'success') {
                    // Show success message
                    const successMessage = document.querySelector('.add-to-cart-success');
                    successMessage.classList.add('show');
                    
                    // Update cart count
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                        cartCount.classList.remove('cart-bump');
                        void cartCount.offsetWidth; // Trigger reflow
                        cartCount.classList.add('cart-bump');
                    }
                    
                    // Reset quantity
                    quantityInput.value = 1;
                    
                    // Hide success message after delay
                    setTimeout(() => {
                        successMessage.classList.remove('show');
                    }, 3000);
                } else {
                    throw new Error(data.message || 'Failed to add to cart');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message === 'The operation was aborted.' 
                    ? 'Request timed out. Please try again.' 
                    : error.message || 'Failed to add to cart. Please try again.');
                
                // Reset states on error
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>