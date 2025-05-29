<?php
require_once __DIR__ . '/config.php';

if (!isset($page_title)) {
    $page_title = "E-Czar"; // Default title if not set
}

// Initialize cart count if not set or force refresh
if (isset($_SESSION['user_id'])) {
    // Get actual cart count from database
    $stmt = $conn->prepare("
        SELECT SUM(quantity) as count 
        FROM user_carts 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $_SESSION['cart_count'] = $result->fetch_assoc()['count'] ?? 0;
} else {
    $_SESSION['cart_count'] = 0;
}

// Database connection and categories query - using ORDER BY id to maintain consistent order
$categories = [];
if (isset($conn)) {
    // Force refresh the categories from database with product counts
    $stmt = $conn->prepare("
        SELECT SQL_NO_CACHE 
            c.id, 
            c.name, 
            c.icon,
            (
                SELECT COUNT(DISTINCT p.id) 
                FROM products p 
                WHERE p.category_id = c.id 
                AND p.status = 'active'
            ) as product_count
        FROM categories c 
        WHERE c.status = 'active' 
        ORDER BY c.id ASC
    ");
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Ensure product_count is set for each category
    foreach ($categories as &$category) {
        $category['product_count'] = intval($category['product_count'] ?? 0);
    }
    unset($category); // Break the reference
    
    // If we're on a category page, ensure the current category is correct
    if (isset($_SESSION['current_category'])) {
        foreach ($categories as &$cat) {
            if ($cat['id'] === $_SESSION['current_category']['id']) {
                // Preserve the product count when updating from session
                $product_count = $cat['product_count'];
                $cat = $_SESSION['current_category'];
                $cat['product_count'] = $product_count;
            }
        }
        unset($cat); // Break the reference
    }
    
    // Debug output for category menu
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        echo "<!-- Debug Categories Menu:\n";
        print_r($categories);
        echo "\n-->";
    }
}

// Get current category ID from URL if it exists
$current_category_id = isset($_GET['id']) ? intval($_GET['id']) : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    /* Dark Sunset Theme */
    :root {
        --sunset-dark: #1a1a2e;
        --sunset-darker: #16213e;
        --sunset-orange: #ff7b25;
        --sunset-pink: #ff4d6d;
        --sunset-purple: #6a2c70;
        --sunset-red: #ff2e63;
        --sunset-yellow: #ffd32d;
        --text-light: #f8f9fa;
        --text-muted: #adb5bd;
        --success-color: #28a745;
        --error-color: #dc3545;
        --sunset-glow: rgba(255, 123, 37, 0.7);
        --header-glow: 0 0 15px rgba(255, 123, 37, 0.4);
    }

    /* Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, var(--sunset-dark), var(--sunset-darker));
        color: var(--text-light);
        min-height: 100vh;
    }

    /* Header Structure */
    .top-bar {
        background-color: var(--sunset-darker);
        color: var(--text-light);
        padding: 8px 0;
        font-size: 12px;
        border-bottom: 1px solid var(--sunset-purple);
        animation: slideDown 0.5s ease-out;
    }

    .top-bar-container, 
    .header-container,
    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    .top-bar-container {
        display: flex;
        justify-content: flex-end;
    }

    .top-links {
        display: flex;
        gap: 20px;
    }

    .top-links a {
        color: var(--text-light);
        text-decoration: none;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 5px;
        opacity: 0;
        animation: fadeInRight 0.5s ease-out forwards;
    }

    .top-links a:nth-child(1) { animation-delay: 0.1s; }
    .top-links a:nth-child(2) { animation-delay: 0.2s; }
    .top-links a:nth-child(3) { animation-delay: 0.3s; }
    .top-links a:nth-child(4) { animation-delay: 0.4s; }
    .top-links a:nth-child(5) { animation-delay: 0.5s; }

    .top-links a:hover {
        color: var(--sunset-orange);
        text-shadow: var(--header-glow);
    }

    /* Main Header */
    .main-header {
        background: linear-gradient(135deg, var(--sunset-darker), var(--sunset-dark));
        padding: 15px 0;
        border-bottom: 2px solid var(--sunset-orange);
    }

    .header-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .logo {
        color: var(--text-light);
        font-size: 28px;
        font-weight: bold;
        text-decoration: none;
        text-shadow: var(--header-glow);
        transition: all 0.3s;
        animation: popIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .logo:hover {
        transform: scale(1.05);
    }

    /* Search Bar */
    .search-container {
        position: relative;
        width: 60%;
        max-width: 600px;
        margin: 10px 0;
        opacity: 0;
        animation: fadeInRight 0.5s ease-out 0.3s forwards;
    }

    .search-bar {
        display: flex;
        width: 100%;
    }

    .search-bar input {
        width: 100%;
        padding: 10px 15px;
        background: var(--sunset-darker);
        border: 1px solid var(--sunset-purple);
        border-radius: 4px 0 0 4px;
        color: var(--text-light);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .search-bar input::placeholder {
        color: var(--text-light);
        opacity: 0.7;
    }

    .search-bar button {
        background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
        color: white;
        border: none;
        padding: 0 20px;
        border-radius: 0 4px 4px 0;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .search-bar button:hover {
        background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
        transform: scale(1.1);
    }

    /* Header Icons */
    .header-icons {
        display: flex;
        gap: 20px;
        align-items: center;
        flex-wrap: wrap;
    }

    .header-icons a {
        color: var(--text-light);
        text-decoration: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 12px;
        transition: all 0.3s;
        position: relative;
        opacity: 0;
        animation: fadeInRight 0.5s ease-out forwards;
    }

    .header-icons a:nth-child(1) { animation-delay: 0.4s; }
    .header-icons a:nth-child(2) { animation-delay: 0.5s; }
    .header-icons a:nth-child(3) { animation-delay: 0.6s; }

    .header-icons a:hover {
        color: var(--sunset-orange);
        transform: translateY(-2px);
    }

    .header-icons i {
        font-size: 20px;
        margin-bottom: 3px;
    }

    .cart-count {
        background: var(--sunset-red);
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 10px;
        font-weight: bold;
        min-width: 18px;
        text-align: center;
        position: absolute;
        top: -5px;
        right: -5px;
        animation: popIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .admin-badge {
        color: var(--sunset-yellow);
        position: absolute;
        top: -5px;
        left: -5px;
        font-size: 12px;
        background: rgba(106, 44, 112, 0.7);
        border-radius: 50%;
        padding: 2px;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: glowPulse 2s infinite;
    }

    /* Admin Links */
    .admin-links {
        display: flex;
        gap: 10px;
        margin-right: 15px;
        flex-wrap: wrap;
    }

    .admin-links a {
        color: var(--sunset-yellow);
        background-color: rgba(106, 44, 112, 0.3);
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.3s;
        border: 1px solid var(--sunset-purple);
        opacity: 0;
        animation: fadeInRight 0.5s ease-out forwards;
    }

    .admin-links a:nth-child(1) { animation-delay: 0.3s; }
    .admin-links a:nth-child(2) { animation-delay: 0.4s; }
    .admin-links a:nth-child(3) { animation-delay: 0.5s; }

    .admin-links a:hover {
        background-color: var(--sunset-purple);
        color: var(--sunset-yellow);
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(255, 211, 45, 0.3);
    }

    /* Main Navigation */
    .main-nav {
        background: var(--sunset-darker);
        border-bottom: 1px solid var(--sunset-purple);
        position: relative;
        z-index: 100;
    }

    .nav-container {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 30px;
        padding: 10px 15px;
    }

    .nav-links {
        display: flex;
        gap: 20px;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .nav-links li {
        opacity: 0;
        animation: fadeInUp 0.5s ease forwards;
    }

    .nav-links li:nth-child(1) { animation-delay: 0.1s; }
    .nav-links li:nth-child(2) { animation-delay: 0.2s; }
    .nav-links li:nth-child(3) { animation-delay: 0.3s; }

    .nav-links a {
        color: var(--text-light);
        text-decoration: none;
        font-weight: 500;
        padding: 8px 12px;
        border-radius: 4px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .nav-links a::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: var(--sunset-orange);
        transform: translateX(-50%);
        transition: width 0.3s ease;
    }

    .nav-links a:hover::before {
        width: 100%;
    }

    .nav-links a:hover {
        color: var(--sunset-orange);
        text-shadow: var(--header-glow);
    }

    .nav-links a.active {
        color: var(--sunset-orange);
        background: rgba(255, 123, 37, 0.1);
    }

    .nav-links a.active::before {
        width: 100%;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Category Dropdown */
    .category-dropdown {
        position: relative;
    }

    .category-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background: var(--sunset-darker);
        border: 1px solid var(--sunset-purple);
        border-radius: 0 0 8px 8px;
        width: 250px;
        z-index: 1000;
        box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        max-height: 70vh;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--sunset-orange) var(--sunset-darker);
        transform-origin: top center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0;
        transform: translateY(-10px);
    }

    .category-menu.show {
        display: block;
        opacity: 1;
        transform: translateY(0);
    }

    .category-dropdown .fa-chevron-down {
        transition: transform 0.3s ease;
        margin-left: 5px;
        font-size: 0.8em;
    }

    .category-dropdown.active .fa-chevron-down {
        transform: rotate(180deg);
    }

    .category-menu::-webkit-scrollbar {
        width: 8px;
    }

    .category-menu::-webkit-scrollbar-thumb {
        background: var(--sunset-orange);
        border-radius: 4px;
    }

    .category-menu a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 15px;
        color: var(--text-light);
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0;
        transform: translateX(-10px);
    }

    .category-menu a:nth-child(1) { animation-delay: 0.7s; }
    .category-menu a:nth-child(2) { animation-delay: 0.75s; }
    .category-menu a:nth-child(3) { animation-delay: 0.8s; }
    .category-menu a:nth-child(4) { animation-delay: 0.85s; }
    .category-menu a:nth-child(5) { animation-delay: 0.9s; }

    .category-menu a:hover {
        background: rgba(255, 123, 37, 0.2);
        color: var(--sunset-orange);
        transform: translateX(5px);
    }

    .category-count {
        background: rgba(255, 123, 37, 0.2);
        color: var(--text-muted);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        margin-left: auto;
        transition: all 0.2s;
    }

    .category-menu a:hover .category-count {
        background: var(--sunset-orange);
        color: var(--text-light);
    }

    .category-dropdown:hover .category-menu a {
        opacity: 1;
        transform: translateX(0);
    }

    /* Enhanced hover animations */
    .top-links a:hover i {
        animation: popIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .header-icons a:hover i {
        animation: popIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .cart-count {
        animation: popIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .admin-badge {
        animation: glowPulse 2s infinite;
    }

    /* Navigation animations */
    .nav-links {
        opacity: 0;
        animation: slideDown 0.5s ease-out 0.6s forwards;
    }

    .nav-links li {
        opacity: 0;
        animation: fadeInRight 0.5s ease-out forwards;
    }

    .nav-links li:nth-child(1) { animation-delay: 0.7s; }
    .nav-links li:nth-child(2) { animation-delay: 0.8s; }
    .nav-links li:nth-child(3) { animation-delay: 0.9s; }

    /* Search bar animations */
    .search-bar input:focus {
        transform: scale(1.02);
        box-shadow: 0 0 15px var(--sunset-glow);
    }

    .search-bar button {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .search-bar button:hover {
        transform: scale(1.1);
    }

    /* Cart animation */
    .cart-icon.animate {
        animation: cartBounce 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .header-container {
            flex-direction: column;
            gap: 15px;
            animation: slideDown 0.5s ease-out;
        }
        
        .search-container {
            width: 100%;
            order: 3;
            animation-delay: 0.2s;
        }
        
        .top-links {
            gap: 10px;
            font-size: 11px;
        }
        
        .nav-links {
            overflow-x: auto;
            padding: 10px 0;
        }

        .category-dropdown {
            position: static;
        }

        .category-menu {
            width: 100%;
            position: static;
            border-radius: 0;
            box-shadow: none;
            max-height: 50vh;
        }

        .admin-links {
            margin-right: 0;
            margin-bottom: 10px;
            justify-content: center;
            width: 100%;
            order: -1;
        }
        
        .header-icons {
            justify-content: center;
            width: 100%;
            gap: 15px;
            animation-delay: 0.3s;
        }
    }

    /* Loading animation for search */
    .search-loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 123, 37, 0.2),
            transparent
        );
        animation: searchLoading 1.5s infinite;
    }

    @keyframes searchLoading {
        from { transform: translateX(-100%); }
        to { transform: translateX(100%); }
    }

    /* Header Animation Keyframes */
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes popIn {
        0% {
            opacity: 0;
            transform: scale(0.8);
        }
        70% {
            transform: scale(1.1);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes cartBounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }

    @keyframes glowPulse {
        0% { box-shadow: 0 0 5px var(--sunset-glow); }
        50% { box-shadow: 0 0 20px var(--sunset-glow); }
        100% { box-shadow: 0 0 5px var(--sunset-glow); }
    }
    </style>
</head>
<body>
    <!-- Top Utility Bar -->
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-links">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="/ecommerce/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                <?php else: ?>
                    <a href="/ecommerce/login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                <?php endif; ?>
                <a href="/ecommerce/about.php"><i class="bi bi-info-circle"></i> About Us</a>
                <a href="/ecommerce/support.php"><i class="bi bi-question-circle"></i> Support</a>
                <a href="/ecommerce/feedback.php"><i class="bi bi-chat-dots"></i> Feedback</a>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="header-container">
            <a href="/ecommerce/index.php" class="logo" aria-label="E-Czar Home">E-Czar</a>
            
            <div class="search-container">
                <form class="search-bar" action="/ecommerce/search.php" method="GET" role="search">
                    <input 
                        type="text" 
                        name="query" 
                        placeholder="Search products..." 
                        aria-label="Search products"
                        autocomplete="off"
                    >
                    <button type="submit" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="header-icons">
                <?php
                // Debug session variables
                if (isset($_SESSION['user_id'])) {
                    echo "<!-- Debug: User ID: " . $_SESSION['user_id'] . " -->";
                    echo "<!-- Debug: Role: " . ($_SESSION['user_role'] ?? 'not set') . " -->";
                }
                
                if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <div class="admin-links">
                        <a href="/ecommerce/admin/admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a href="/ecommerce/admin/admin_users.php"><i class="fas fa-users"></i> Users</a>
                        <a href="/ecommerce/admin/admin_products.php"><i class="fas fa-boxes"></i> Products</a>
                    </div>
                <?php endif; ?>
                
                <a href="/ecommerce/account.php" aria-label="My Account">
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <span class="admin-badge" title="Administrator">
                            <i class="fas fa-shield-alt"></i>
                        </span>
                    <?php endif; ?>
                    <i class="fas fa-user"></i>
                    <span>Account</span>
                </a>

                <a href="/ecommerce/cart.php" aria-label="Shopping Cart" style="position: relative;">
                    <i class="fas fa-shopping-cart cart-icon"></i>
                    <span>Cart</span>
                    <span class="cart-count"><?= $_SESSION['cart_count'] ?></span>
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="main-nav">
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="/ecommerce/index.php">Home</a></li>
                <li class="category-dropdown">
                    <a href="/ecommerce/categories.php">Categories <i class="fas fa-chevron-down"></i></a>
                    <div class="category-menu">
                        <?php 
                        // Sort categories by ID to ensure consistent order
                        usort($categories, function($a, $b) {
                            return $a['id'] - $b['id'];
                        });
                        
                        foreach ($categories as $category): 
                            // Debug output for each category
                            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                                echo "<!-- Category Menu Item: ID={$category['id']}, Name={$category['name']}, Count={$category['product_count']} -->\n";
                            }
                            
                            // Check if this is the current category
                            $is_current = ($current_category_id !== null && $current_category_id === (int)$category['id']);
                            if ($is_current && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                                echo "<!-- Current category match: {$category['name']} -->\n";
                            }
                        ?>
                            <a href="/ecommerce/category.php?id=<?= $category['id'] ?>" 
                               class="<?= $is_current ? 'active' : '' ?>"
                               aria-label="<?= htmlspecialchars($category['name']) ?>"
                               data-category-id="<?= $category['id'] ?>">
                                <i class="<?= htmlspecialchars($category['icon']) ?>"></i>
                                <?= htmlspecialchars($category['name']) ?>
                                <span class="category-count"><?= intval($category['product_count']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main class="main-content">

<script>
// Header animation utilities
document.addEventListener('DOMContentLoaded', function() {
    // Cart animation
    const cartIcon = document.querySelector('.cart-icon');
    const cartCount = document.querySelector('.cart-count');
    let cartTimeout;

    function animateCart() {
        cartIcon.classList.add('animate');
        setTimeout(() => cartIcon.classList.remove('animate'), 500);
    }

    // Animate cart when count changes
    if (cartCount) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'characterData' || mutation.type === 'childList') {
                    animateCart();
                }
            });
        });

        observer.observe(cartCount, {
            characterData: true,
            childList: true,
            subtree: true
        });
    }

    // Search animation
    const searchInput = document.querySelector('.search-bar input');
    const searchContainer = document.querySelector('.search-container');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchContainer.classList.add('search-loading');
            
            searchTimeout = setTimeout(() => {
                searchContainer.classList.remove('search-loading');
            }, 500);
        });
    }

    // Category menu animation
    const categoryLinks = document.querySelectorAll('.category-menu a');
    categoryLinks.forEach((link, index) => {
        link.style.transitionDelay = `${index * 0.05}s`;
    });

    // Admin badge pulse animation
    const adminBadge = document.querySelector('.admin-badge');
    if (adminBadge) {
        adminBadge.addEventListener('mouseover', () => {
            adminBadge.style.animation = 'none';
            adminBadge.offsetHeight; // Trigger reflow
            adminBadge.style.animation = 'glowPulse 2s infinite';
        });
    }

    // Smooth scroll for navigation links
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Enhanced hover effects for icons
    document.querySelectorAll('.top-links a, .header-icons a').forEach(link => {
        const icon = link.querySelector('i');
        if (icon) {
            link.addEventListener('mouseenter', () => {
                icon.style.animation = 'none';
                icon.offsetHeight; // Trigger reflow
                icon.style.animation = 'popIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
            });
        }
    });

    // Intersection Observer for scroll animations
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = entry.target.dataset.animation;
                }
            });
        },
        { threshold: 0.1 }
    );

    // Observe elements with data-animation attribute
    document.querySelectorAll('[data-animation]').forEach(el => observer.observe(el));

    // Category dropdown functionality
    const categoryDropdown = document.querySelector('.category-dropdown');
    const categoryMenu = document.querySelector('.category-menu');
    
    if (categoryDropdown && categoryMenu) {
        let isMouseOverDropdown = false;
        
        // Toggle on hover
        categoryDropdown.addEventListener('mouseenter', () => {
            isMouseOverDropdown = true;
            categoryDropdown.classList.add('active');
            categoryMenu.classList.add('show');
        });
        
        categoryDropdown.addEventListener('mouseleave', () => {
            isMouseOverDropdown = false;
            setTimeout(() => {
                if (!isMouseOverDropdown) {
                    categoryDropdown.classList.remove('active');
                    categoryMenu.classList.remove('show');
                }
            }, 200);
        });
        
        // Toggle on click (for mobile)
        categoryDropdown.querySelector('a').addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                categoryDropdown.classList.toggle('active');
                categoryMenu.classList.toggle('show');
            }
        });
        
        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!categoryDropdown.contains(e.target)) {
                categoryDropdown.classList.remove('active');
                categoryMenu.classList.remove('show');
            }
        });
    }
});
</script>