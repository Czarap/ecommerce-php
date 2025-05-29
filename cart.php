<?php
session_start();

$page_title = "Your Cart";
include 'includes/config.php';
include 'includes/header.php';

// Check login status
$is_logged_in = isset($_SESSION['user_id']);

if (!$is_logged_in) {
    ?>
    <script>
        window.location.href = 'login.php';
    </script>
    <?php
    exit();
}

// Fetch cart items
$user_id = $_SESSION['user_id'];
$cart_items = [];
$total = 0;

try {
    $stmt = $conn->prepare("
        SELECT p.id, p.name, p.price, p.image, uc.quantity 
        FROM user_carts uc
        JOIN products p ON uc.product_id = p.id
        WHERE uc.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Calculate total
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch (Exception $e) {
    $error = "Error loading cart: " . $e->getMessage();
}
?>
<style>
    /* Cart Container */
    .cart-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 20px;
        color: #f0e6d2;
    }

    /* Table Styling */
    .cart-table {
        width: 100%;
        border-collapse: collapse;
        margin: 2rem 0;
        box-shadow: 0 2px 15px rgba(210, 105, 30, 0.3);
        background: rgba(40, 30, 45, 0.8);
    }

    .cart-table th {
        background: linear-gradient(to right, #8B4513, #CD5C5C);
        color: #f8f8f8;
        text-align: left;
        padding: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .cart-table td {
        padding: 12px;
        border-bottom: 1px solid rgba(205, 92, 92, 0.3);
        vertical-align: middle;
    }

    .cart-table tr:hover {
        background-color: rgba(75, 55, 65, 0.6);
    }

    /* Product Image */
    .cart-table img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 15px;
        vertical-align: middle;
        border: 1px solid #CD5C5C;
    }

    /* Quantity Input */
    .cart-table input[type="number"] {
        width: 60px;
        padding: 8px;
        background: rgba(30, 20, 35, 0.8);
        border: 1px solid #8B4513;
        border-radius: 4px;
        color: #f0e6d2;
    }

    /* Action Buttons */
    .remove-item {
        background: linear-gradient(to bottom, #CD5C5C, #8B0000);
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .remove-item:hover {
        background: linear-gradient(to bottom, #8B0000, #CD5C5C);
        transform: translateY(-1px);
    }

    /* Total Row */
    .cart-table tr.total-row {
        font-weight: bold;
        background-color: rgba(75, 55, 65, 0.8);
    }

    .cart-table tr.total-row td {
        border-top: 2px solid #CD5C5C;
    }

    /* Empty Cart Message */
    .cart-empty {
        text-align: center;
        padding: 3rem;
        color: #CD5C5C;
        font-size: 1.2rem;
    }

    /* Links */
    a {
        color: #CD5C5C;
        text-decoration: none;
        transition: color 0.3s;
    }

    a:hover {
        color: #f0e6d2;
        text-decoration: underline;
    }

    /* Buttons */
    .continue-shopping {
        display: inline-block;
        padding: 10px 20px;
        background: rgba(40, 30, 45, 0.8);
        border: 1px solid #8B4513;
        border-radius: 4px;
        margin-right: 15px;
    }

    .checkout-btn {
        display: inline-block;
        padding: 10px 20px;
        background: linear-gradient(to right, #8B4513, #CD5C5C);
        color: white;
        border-radius: 4px;
        font-weight: bold;
    }

    .checkout-btn:hover {
        background: linear-gradient(to right, #CD5C5C, #8B4513);
        text-decoration: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .cart-table thead {
            display: none;
        }
        
        .cart-table tr {
            display: block;
            margin-bottom: 20px;
            border: 1px solid #8B4513;
            background: rgba(40, 30, 45, 0.9);
        }
        
        .cart-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-table td::before {
            content: attr(data-label);
            font-weight: bold;
            margin-right: 20px;
            color: #CD5C5C;
        }
    }

    /* Empty Cart Styling */
    .empty-cart-container {
        text-align: center;
        padding: 4rem 2rem;
        background: rgba(26, 26, 46, 0.6);
        border-radius: 15px;
        border: 1px solid var(--sunset-orange);
        max-width: 600px;
        margin: 3rem auto;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        animation: fadeInUp 0.6s ease-out;
    }

    .empty-cart-icon {
        font-size: 4rem;
        color: var(--sunset-orange);
        margin-bottom: 1.5rem;
        opacity: 0.8;
        animation: cartBounce 2s infinite;
    }

    .empty-cart-message {
        font-size: 1.5rem;
        color: var(--sunset-light);
        margin-bottom: 2rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .continue-shopping-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 30px;
        background: linear-gradient(135deg, var(--sunset-orange), var(--sunset-red));
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 4px 15px rgba(255, 123, 37, 0.3);
    }

    .continue-shopping-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 123, 37, 0.4);
        background: linear-gradient(135deg, var(--sunset-red), var(--sunset-purple));
        color: white;
        text-decoration: none;
    }

    .continue-shopping-btn i {
        transition: transform 0.3s ease;
    }

    .continue-shopping-btn:hover i {
        transform: translateX(-5px);
    }

    @keyframes cartBounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
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
</style>

<div class="cart-container">
    <h1>Your Shopping Cart</h1>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart-container">
            <div class="empty-cart-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <p class="empty-cart-message">Your cart is empty</p>
            <a href="shop.php" class="continue-shopping-btn">
                <i class="fas fa-arrow-left"></i>
                Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr data-product-id="<?= $item['id'] ?>">
                        <td data-label="Product">
                            <div class="cart-item">
                                <?php if ($item['image']): ?>
                                    <img src="uploads/products/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                <?php else: ?>
                                    <div style="width: 80px; height: 80px; background: var(--sunset-darker); display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                        <i class="fas fa-image" style="font-size: 2rem; color: var(--sunset-text); opacity: 0.3;"></i>
                                    </div>
                                <?php endif; ?>
                                <?= htmlspecialchars($item['name']) ?>
                            </div>
                        </td>
                        <td data-label="Price">₱<?= number_format($item['price'], 2) ?></td>
                        <td data-label="Quantity">
                            <input type="number" value="<?= $item['quantity'] ?>" min="1">
                        </td>
                        <td data-label="Subtotal">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        <td data-label="Action">
                            <button class="remove-item">
                                <i class="fas fa-trash-alt"></i> Remove
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3"><strong>Total</strong></td>
                    <td>₱<?= number_format($total, 2) ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        
        <div class="cart-actions">
            <a href="shop.php" class="continue-shopping">Continue Shopping</a>
            <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update cart count in header
    function updateCartCount(count) {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = count;
            cartCount.classList.remove('cart-bump');
            void cartCount.offsetWidth; // Trigger reflow
            cartCount.classList.add('cart-bump');
        }
    }

    // Update total price
    function updateTotal() {
        let total = 0;
        const rows = document.querySelectorAll('.cart-table tr:not(.total-row)');
        
        if (rows.length === 0) {
            location.reload();
            return;
        }

        rows.forEach(row => {
            const priceCell = row.querySelector('[data-label="Price"]');
            const quantityInput = row.querySelector('input[type="number"]');
            
            if (priceCell && quantityInput) {
                const price = parseFloat(priceCell.textContent.replace('₱', '').replace(',', ''));
                const quantity = parseInt(quantityInput.value);
                if (!isNaN(price) && !isNaN(quantity)) {
                    const subtotal = price * quantity;
                    const subtotalCell = row.querySelector('[data-label="Subtotal"]');
                    if (subtotalCell) {
                        subtotalCell.textContent = `₱${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                    }
                    total += subtotal;
                }
            }
        });

        const totalCell = document.querySelector('.total-row td:nth-child(4)');
        if (totalCell) {
            totalCell.textContent = `₱${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }
    }

    // Quantity update handler
    document.querySelectorAll('.cart-table input[type="number"]').forEach(input => {
        input.addEventListener('change', async function() {
            const productId = this.closest('tr').dataset.productId;
            const newQuantity = this.value;
            const originalQuantity = this.defaultValue;
            
            try {
                const response = await fetch('update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=${newQuantity}`
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    this.defaultValue = newQuantity;
                    updateTotal();
                    updateCartCount(data.cart_count);
                } else {
                    this.value = originalQuantity;
                    alert(data.message || 'Failed to update quantity');
                }
            } catch (error) {
                console.error('Error:', error);
                this.value = originalQuantity;
                alert('Failed to update quantity');
            }
        });
    });
    
    // Remove item handler
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', async function() {
            if (!confirm('Remove this item from cart?')) {
                return;
            }

            const row = this.closest('tr');
            const productId = row.dataset.productId;
            
            try {
                const response = await fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}`
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Animate row removal
                    row.style.transition = 'opacity 0.3s ease';
                    row.style.opacity = '0';
                    
                    setTimeout(() => {
                        row.remove();
                        updateCartCount(data.cart_count);
                        
                        // Check remaining items
                        const remainingItems = document.querySelectorAll('.cart-table tr:not(.total-row)').length;
                        if (remainingItems <= 1) { // 1 because total row is still there
                            location.reload(); // Refresh only if cart is empty
                        } else {
                            updateTotal(); // Otherwise just update the total
                        }
                    }, 300);
                } else {
                    throw new Error(data.message || 'Failed to remove item');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(`Failed to remove item: ${error.message}`);
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>