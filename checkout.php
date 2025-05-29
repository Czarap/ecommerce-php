<?php
// Must be first line in file
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/config.php';

// Process order when form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Get shipping details
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $address = sanitize($_POST['address']);
        $city = sanitize($_POST['city']);
        $state = sanitize($_POST['state']);
        $zip = sanitize($_POST['zip']);
        $payment_method = sanitize($_POST['payment_method']);

        // Validate payment details based on method
        $payment_details = [];
        
        if ($payment_method === 'Credit Card') {
            // Validate credit card details
            if (empty($_POST['card_number']) || empty($_POST['card_expiry']) || 
                empty($_POST['card_cvv']) || empty($_POST['card_name'])) {
                throw new Exception("All credit card fields are required");
            }
            
            // Basic validation
            if (!preg_match('/^[0-9]{16}$/', str_replace(' ', '', $_POST['card_number']))) {
                throw new Exception("Invalid card number");
            }
            if (!preg_match('/(0[1-9]|1[0-2])\/([0-9]{2})/', $_POST['card_expiry'])) {
                throw new Exception("Invalid expiry date format (MM/YY)");
            }
            if (!preg_match('/^[0-9]{3,4}$/', $_POST['card_cvv'])) {
                throw new Exception("Invalid CVV");
            }
            
            $payment_details = [
                'card_number' => substr(str_replace(' ', '', $_POST['card_number']), -4), // Store last 4 digits only
                'card_expiry' => $_POST['card_expiry'],
                'card_name' => sanitize($_POST['card_name'])
            ];
        } 
        elseif ($payment_method === 'Gcash') {
            // Validate GCash details
            if (empty($_POST['gcash_number']) || empty($_POST['gcash_name'])) {
                throw new Exception("All GCash fields are required");
            }
            
            // Basic validation
            if (!preg_match('/^[0-9]{11}$/', str_replace(' ', '', $_POST['gcash_number']))) {
                throw new Exception("Invalid GCash number");
            }
            
            $payment_details = [
                'gcash_number' => sanitize($_POST['gcash_number']),
                'gcash_name' => sanitize($_POST['gcash_name'])
            ];
        }

        // Store payment details as JSON
        $payment_details_json = !empty($payment_details) ? json_encode($payment_details) : null;

        // Calculate total from cart
        $stmt = $conn->prepare("SELECT SUM(p.price * uc.quantity) as total 
                               FROM user_carts uc 
                               JOIN products p ON uc.product_id = p.id 
                               WHERE uc.user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        // Begin transaction
        $conn->begin_transaction();

        // 1. Create order record
        $stmt = $conn->prepare("
            SELECT COUNT(*) as column_exists 
            FROM information_schema.columns 
            WHERE table_name = 'orders' 
            AND column_name = 'payment_details'
        ");
        $stmt->execute();
        $column_exists = $stmt->get_result()->fetch_assoc()['column_exists'] > 0;

        if ($column_exists) {
            // Use query with payment_details
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total, shipping_name, shipping_email, shipping_address, shipping_city, shipping_state, shipping_zip, payment_method, payment_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("idssssssss", $_SESSION['user_id'], $total, $name, $email, $address, $city, $state, $zip, $payment_method, $payment_details_json);
        } else {
            // Use query without payment_details
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total, shipping_name, shipping_email, shipping_address, shipping_city, shipping_state, shipping_zip, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idsssssss", $_SESSION['user_id'], $total, $name, $email, $address, $city, $state, $zip, $payment_method);
        }
        $stmt->execute();
        $order_id = $conn->insert_id;

        // 2. Add order items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                               SELECT ?, product_id, quantity, price 
                               FROM user_carts uc 
                               JOIN products p ON uc.product_id = p.id 
                               WHERE user_id = ?");
        $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
        $stmt->execute();

        // 3. Clear cart
        $stmt = $conn->prepare("DELETE FROM user_carts WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Update session
        $_SESSION['cart_count'] = 0;
        $_SESSION['order_completed'] = true;
        $_SESSION['order_id'] = $order_id;

        // Redirect based on payment method
        switch($payment_method) {
            case 'Cash on Delivery':
                header("Location: account.php?tab=orders&order_id=" . $order_id . "&status=pending");
                break;
            
            case 'Gcash':
                // For GCash, redirect to processing page first
                header("Location: process_payment.php?payment_method=gcash&order_id=" . $order_id);
                break;
            
            case 'Credit Card':
                // For Credit Card, redirect to processing page first
                header("Location: process_payment.php?payment_method=credit_card&order_id=" . $order_id);
                break;
            
            default:
                header("Location: order_confirmed.php");
        }
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Order failed: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>
<style>
/* Dark Sunset Theme for Checkout */
.checkout-container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 20px;
    color: #f0e6d2;
}

.checkout-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.checkout-section {
    background: rgba(40, 30, 45, 0.8);
    padding: 25px;
    border-radius: 8px;
    border: 1px solid #8B4513;
    box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
}

.section-title {
    color: #CD5C5C;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #8B4513;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #f0e6d2;
}

.form-control {
    width: 100%;
    padding: 10px;
    background: rgba(30, 20, 35, 0.8);
    border: 1px solid #8B4513;
    border-radius: 4px;
    color: #f0e6d2;
}

.payment-methods {
    margin-top: 20px;
}

.payment-method {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.order-summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px dashed rgba(205, 92, 92, 0.3);
}

.order-total {
    font-weight: bold;
    font-size: 1.2rem;
    margin-top: 20px;
    color: #CD5C5C;
}

.btn-checkout {
    display: block;
    width: 100%;
    padding: 12px;
    background: linear-gradient(to right, #8B4513, #CD5C5C);
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 20px;
}

.btn-checkout:hover {
    background: linear-gradient(to right, #CD5C5C, #8B4513);
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
}

/* Add these styles to your existing CSS */
.payment-details {
    margin-top: 20px;
    padding: 15px;
    background: rgba(20, 15, 25, 0.5);
    border-radius: 4px;
    border: 1px solid rgba(139, 69, 19, 0.3);
}

.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.col-6 {
    flex: 0 0 calc(50% - 7.5px);
}

.payment-details .form-control {
    background: rgba(30, 20, 35, 0.8);
    border: 1px solid #8B4513;
    transition: all 0.3s ease;
    color: white !important;
}

.payment-details .form-control:focus {
    border-color: #CD5C5C;
    box-shadow: 0 0 0 2px rgba(205, 92, 92, 0.2);
}

.payment-details .form-control::placeholder {
    color: rgba(240, 230, 210, 0.5);
}

.payment-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 15px;
}

.payment-method {
    position: relative;
    margin: 0;
}

.payment-method input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.payment-label {
    display: flex;
    flex-direction: row;
    align-items: center;
    padding: 10px 15px;
    background: rgba(30, 20, 35, 0.8);
    border: 2px solid #8B4513;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #f0e6d2;
}

.payment-label i {
    font-size: 1.2rem;
    margin-right: 12px;
    margin-bottom: 0;
    color: #CD5C5C;
}

.payment-content {
    display: flex;
    flex-direction: column;
}

.payment-description {
    font-size: 0.75rem;
    color: rgba(240, 230, 210, 0.7);
    margin-top: 2px;
}

.payment-method input[type="radio"]:checked + .payment-label {
    background: linear-gradient(45deg, rgba(139, 69, 19, 0.4), rgba(205, 92, 92, 0.4));
    border-color: #CD5C5C;
    box-shadow: 0 0 10px rgba(205, 92, 92, 0.3);
}

.payment-method input[type="radio"]:focus + .payment-label {
    box-shadow: 0 0 0 2px rgba(205, 92, 92, 0.5);
}

.payment-method:hover .payment-label {
    transform: translateY(-1px);
    border-color: #CD5C5C;
}

@media (max-width: 768px) {
    .payment-label {
        padding: 8px 12px;
    }
    
    .payment-label i {
        font-size: 1rem;
    }
}
</style>

<div class="checkout-container">
    <h1>Checkout</h1>
    
    <?php if (!empty($error)): ?>
        <div class="error-message" style="color: #ff6b6b; padding: 10px; background: rgba(255,0,0,0.1); border-radius: 4px; margin-bottom: 20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="checkout.php">
        <div class="checkout-grid">
            <div class="checkout-section">
                <h2 class="section-title">Shipping Details</h2>
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" required 
                           value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="state">State/Province</label>
                    <input type="text" id="state" name="state" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="zip">ZIP/Postal Code</label>
                    <input type="text" id="zip" name="zip" class="form-control" required>
                </div>
            </div>
            
            <div class="checkout-section">
                <h2 class="section-title">Order Summary</h2>
                
                <?php 
                $total = 0;
                $stmt = $conn->prepare("SELECT p.id, p.name, p.price, uc.quantity 
                                       FROM user_carts uc 
                                       JOIN products p ON uc.product_id = p.id 
                                       WHERE uc.user_id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                foreach ($items as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                ?>
                    <div class="order-summary-item">
                        <span><?= htmlspecialchars($item['name']) ?> (×<?= $item['quantity'] ?>)</span>
                        <span>$<?= number_format($subtotal, 2) ?></span>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-total">
                    <span>Total:</span>
                    <span>$<?= number_format($total, 2) ?></span>
                </div>
                
                <div class="payment-methods">
                    <h3 class="section-title">Payment Method</h3>
                    
                    <div class="payment-options">
                    <div class="payment-method">
                            <input type="radio" id="cod" name="payment_method" value="Cash on Delivery" checked>
                            <label for="cod" class="payment-label">
                                <i class="fas fa-money-bill-wave"></i>
                                <div class="payment-content">
                                    Cash on Delivery
                                    <span class="payment-description">Pay when you receive your order</span>
                                </div>
                            </label>
                    </div>
                    
                    <div class="payment-method">
                        <input type="radio" id="gcash" name="payment_method" value="Gcash">
                            <label for="gcash" class="payment-label">
                                <i class="fas fa-mobile-alt"></i>
                                <div class="payment-content">
                                    GCash
                                    <span class="payment-description">Pay via GCash mobile wallet</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" id="credit-card" name="payment_method" value="Credit Card">
                            <label for="credit-card" class="payment-label">
                                <i class="fas fa-credit-card"></i>
                                <div class="payment-content">
                                    Credit Card
                                    <span class="payment-description">Pay with your credit/debit card</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Credit Card Details -->
                    <div id="credit-card-details" class="payment-details">
                        <div class="form-group">
                            <label for="card-number">Card Number</label>
                            <input type="text" 
                                   id="card-number" 
                                   name="card_number" 
                                   class="form-control" 
                                   pattern="[0-9]{16}" 
                                   maxlength="19" 
                                   placeholder="1234 5678 9012 3456"
                                   style="color: white;">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="card-expiry">Expiry Date</label>
                                <input type="text" 
                                       id="card-expiry" 
                                       name="card_expiry" 
                                       class="form-control" 
                                       pattern="(0[1-9]|1[0-2])\/([0-9]{2})" 
                                       placeholder="MM/YY"
                                       style="color: white;">
                            </div>
                            <div class="form-group col-6">
                                <label for="card-cvv">CVV</label>
                                <input type="text" 
                                       id="card-cvv" 
                                       name="card_cvv" 
                                       class="form-control" 
                                       pattern="[0-9]{3,4}" 
                                       maxlength="4" 
                                       placeholder="123"
                                       style="color: white;">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="card-name">Name on Card</label>
                            <input type="text" 
                                   id="card-name" 
                                   name="card_name" 
                                   class="form-control" 
                                   placeholder="Juan Thousand"
                                   style="color: white;">
                        </div>
                    </div>
                    
                    <!-- GCash Details -->
                    <div id="gcash-details" class="payment-details" style="display: none;">
                        <div class="form-group">
                            <label for="gcash-number">GCash Number</label>
                            <input type="text" 
                                   id="gcash-number" 
                                   name="gcash_number" 
                                   class="form-control" 
                                   pattern="[0-9]{4} [0-9]{3} [0-9]{4}"
                                   maxlength="13" 
                                   placeholder="09XX XXX XXXX"
                                   style="color: white;"
                                   title="Please enter a valid 11-digit GCash number">
                        </div>
                        <div class="form-group">
                            <label for="gcash-name">GCash Account Name</label>
                            <input type="text" 
                                   id="gcash-name" 
                                   name="gcash_name" 
                                   class="form-control" 
                                   placeholder="Juan Thousand"
                                   style="color: white;">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-checkout">Complete Order</button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const creditCardDetails = document.getElementById('credit-card-details');
    const gcashDetails = document.getElementById('gcash-details');
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    
    // Function to format credit card number with spaces
    function formatCardNumber(e) {
        let input = e.target;
        let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        input.value = formattedValue;
    }

    // Function to format expiry date
    function formatExpiryDate(e) {
        let input = e.target;
        let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        if (value.length > 2) {
            value = value.slice(0, 2) + '/' + value.slice(2, 4);
        }
        input.value = value;
    }

    // Function to format GCash number
    function formatGcashNumber(e) {
        let input = e.target;
        let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        
        // Ensure the number starts with '09'
        if (value.length > 0 && !value.startsWith('09')) {
            value = '09' + value.slice(2);
        }
        
        // Format with spaces after 4th and 7th digits
        if (value.length > 7) {
            value = value.slice(0, 4) + ' ' + value.slice(4, 7) + ' ' + value.slice(7);
        } else if (value.length > 4) {
            value = value.slice(0, 4) + ' ' + value.slice(4);
        }
        
        // Limit to 11 digits total
        if (value.replace(/\s/g, '').length > 11) {
            value = value.slice(0, 13); // 11 digits + 2 spaces
        }
        
        input.value = value;
    }

    // Add event listeners for formatting
    document.getElementById('card-number').addEventListener('input', formatCardNumber);
    document.getElementById('card-expiry').addEventListener('input', formatExpiryDate);
    document.getElementById('gcash-number').addEventListener('input', formatGcashNumber);

    // Function to toggle payment details
    function togglePaymentDetails() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        creditCardDetails.style.display = selectedMethod === 'Credit Card' ? 'block' : 'none';
        gcashDetails.style.display = selectedMethod === 'Gcash' ? 'block' : 'none';
        
        // Update required attributes
        const creditCardInputs = creditCardDetails.querySelectorAll('input');
        const gcashInputs = gcashDetails.querySelectorAll('input');
        
        creditCardInputs.forEach(input => {
            input.required = selectedMethod === 'Credit Card';
        });
        
        gcashInputs.forEach(input => {
            input.required = selectedMethod === 'Gcash';
        });
    }

    // Add event listeners to payment method radio buttons
    paymentMethods.forEach(radio => {
        radio.addEventListener('change', togglePaymentDetails);
    });

    // Initialize form state
    togglePaymentDetails();
});
</script>

<?php include 'includes/footer.php'; ?>