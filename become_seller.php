<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Please login to become a seller";
    header('Location: login.php');
    exit();
}

// Check if already a seller
if (isSeller()) {
    header('Location: seller/dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Update user to seller role
    $stmt = $conn->prepare("UPDATE users SET role = 'seller' WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['user_role'] = 'seller';
        $_SESSION['success_message'] = "Congratulations! You are now a seller.";
        header('Location: seller/dashboard.php');
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating seller status";
    }
}

$page_title = "Become a Seller";
include 'includes/header.php';
?>

<style>
.become-seller-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: rgba(26, 26, 46, 0.8);
    border-radius: 15px;
    border: 1px solid var(--sunset-orange);
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.benefit-card {
    background: rgba(40, 30, 45, 0.8);
    padding: 1.5rem;
    border-radius: 10px;
    border: 1px solid rgba(255, 123, 37, 0.2);
    text-align: center;
}

.benefit-card i {
    font-size: 2rem;
    color: var(--sunset-orange);
    margin-bottom: 1rem;
}

.benefit-card h3 {
    color: var(--sunset-light);
    margin-bottom: 1rem;
}

.benefit-card p {
    color: var(--sunset-text);
    line-height: 1.6;
}

.become-seller-btn {
    display: block;
    width: 100%;
    max-width: 300px;
    margin: 2rem auto;
    padding: 1rem;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}

.become-seller-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 46, 99, 0.5);
}
</style>

<div class="become-seller-container">
    <h1 class="text-center mb-4">Become a Seller</h1>
    
    <div class="benefits-grid">
        <div class="benefit-card">
            <i class="fas fa-store"></i>
            <h3>Your Own Shop</h3>
            <p>Create and manage your own online store with complete control over your products.</p>
        </div>
        
        <div class="benefit-card">
            <i class="fas fa-chart-line"></i>
            <h3>Track Sales</h3>
            <p>Monitor your sales, earnings, and customer feedback all in one place.</p>
        </div>
        
        <div class="benefit-card">
            <i class="fas fa-globe"></i>
            <h3>Reach Customers</h3>
            <p>Connect with customers worldwide and grow your business exponentially.</p>
        </div>
        
        <div class="benefit-card">
            <i class="fas fa-tools"></i>
            <h3>Seller Tools</h3>
            <p>Access powerful tools to manage inventory, process orders, and handle shipping.</p>
        </div>
    </div>
    
    <form method="POST" action="">
        <button type="submit" class="become-seller-btn">
            <i class="fas fa-store"></i> Start Selling Now
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?> 