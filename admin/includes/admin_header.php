<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Check if user is admin, if not redirect to login
adminOnly();

// Get the current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --sunset-darker: #0f1525;
            --sunset-dark: #1a1f35;
            --sunset-purple: #2c1f4a;
            --sunset-orange: #ff7b25;
            --sunset-red: #ff2e63;
            --sunset-light: #f8f9fa;
            --sunset-text: #e1e1e1;
            --sunset-glow: rgba(255, 123, 37, 0.5);
        }

        body {
            background-color: var(--sunset-darker);
            color: var(--sunset-text);
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--sunset-dark), var(--sunset-purple));
            min-height: 100vh;
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 20px;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            border-right: 1px solid rgba(255, 123, 37, 0.1);
        }

        .sidebar .nav-link {
            color: var(--sunset-text);
            padding: 12px 20px;
            margin: 4px 16px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 123, 37, 0.1), transparent);
            transition: 0.5s;
        }

        .sidebar .nav-link:hover:before {
            left: 100%;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 123, 37, 0.1);
            color: var(--sunset-orange);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
            color: white;
            box-shadow: 0 4px 15px rgba(255, 123, 37, 0.3);
        }

        .sidebar .nav-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            background: var(--sunset-darker);
        }

        .admin-header {
            background: linear-gradient(145deg, var(--sunset-dark), var(--sunset-purple));
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 123, 37, 0.1);
        }

        .text-center {
            text-align: center;
        }

        .text-light {
            color: var(--sunset-light) !important;
        }

        .text-muted {
            color: #a8b2d1 !important;
        }

        .back-to-site {
            margin-top: auto;
            border-top: 1px solid rgba(255, 123, 37, 0.1);
            margin-top: 20px;
            padding-top: 20px;
        }

        /* Add some animation for the active state */
        .nav-link.active i {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        .card {
            background: linear-gradient(145deg, var(--sunset-dark), var(--sunset-purple));
            border: 1px solid var(--sunset-purple);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid var(--sunset-purple);
            color: var(--sunset-orange);
        }

        .btn-sunset {
            background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
            border: none;
            color: white;
            transition: all 0.3s;
        }

        .btn-sunset:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px var(--sunset-glow);
        }

        .table {
            color: var(--sunset-text);
        }

        .table thead th {
            background: rgba(0, 0, 0, 0.2);
            border-color: var(--sunset-purple);
            color: white;
        }

        .table td {
            border-color: var(--sunset-purple);
            color: white;
        }

        .product-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--sunset-purple);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .product-thumbnail-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: var(--sunset-purple);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--sunset-dark);
        }

        .table tbody tr {
            background: var(--sunset-darker);
        }

        .table tbody tr:hover {
            background: var(--sunset-dark);
        }

        .table td, .table th {
            color: var(--sunset-text);
            border-color: rgba(255, 123, 37, 0.1);
        }

        /* Statistics Cards */
        .card h4 {
            color: var(--sunset-text);
            font-size: 2rem;
            margin: 0;
            font-weight: 600;
        }

        .card .text-muted {
            color: var(--sunset-orange) !important;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .card .bi {
            opacity: 0.9;
        }

        .card-body {
            background: var(--sunset-darker);
            border-radius: 12px;
        }

        /* Table Styles */
        .table {
            margin: 0;
            background: var(--sunset-darker);
        }

        .table thead tr {
            background: linear-gradient(145deg, var(--sunset-dark), var(--sunset-purple));
        }

        .table thead th {
            color: var(--sunset-orange);
            font-weight: 500;
            border-bottom: 2px solid rgba(255, 123, 37, 0.1);
            padding: 1rem;
        }

        .table tbody tr {
            background: rgba(26, 26, 46, 0.95);
            border-bottom: 1px solid rgba(255, 123, 37, 0.1);
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(44, 31, 74, 0.95);
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table td {
            padding: 1rem;
            color: var(--sunset-text) !important;
        }

        /* Card Text Colors */
        .card h4, 
        .card h5, 
        .card h6,
        .card p,
        .card span:not(.badge),
        .table td,
        .table th {
            color: var(--sunset-text) !important;
        }

        /* Statistics Cards */
        .card h4 {
            color: var(--sunset-text) !important;
            font-size: 2rem;
            margin: 0;
            font-weight: 600;
        }

        .card .text-muted {
            color: var(--sunset-orange) !important;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Table Header Text */
        .table th {
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            color: var(--sunset-orange) !important;
        }

        /* View All Link */
        .view-all,
        .btn-view-all {
            color: var(--sunset-orange) !important;
            text-decoration: none;
            transition: all 0.3s;
        }

        .view-all:hover,
        .btn-view-all:hover {
            color: var(--sunset-red) !important;
        }

        /* Table Header Title */
        .table-header h5 {
            color: var(--sunset-text) !important;
            margin: 0;
        }

        /* Override any Bootstrap text colors */
        .text-white,
        .text-light,
        td.text-white,
        td.text-light {
            color: var(--sunset-text) !important;
        }

        /* Custom Badge Colors */
        .badge.bg-danger {
            background: linear-gradient(to right, var(--sunset-red), var(--sunset-pink)) !important;
        }

        .badge.bg-success {
            background: linear-gradient(to right, #2ecc71, #27ae60) !important;
        }

        .badge.bg-info {
            background: linear-gradient(to right, var(--sunset-purple), var(--sunset-pink)) !important;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4 class="text-light">Admin Panel</h4>
            <?php if(isset($_SESSION['name'])): ?>
                <p class="text-muted mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            <?php endif; ?>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $current_page === 'admin_dashboard.php' ? 'active' : ''; ?>" href="/ecommerce/admin/admin_dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link <?php echo $current_page === 'admin_products.php' ? 'active' : ''; ?>" href="/ecommerce/admin/admin_products.php">
                <i class="bi bi-box-seam"></i> Products
            </a>
            <a class="nav-link <?php echo $current_page === 'admin_categories.php' ? 'active' : ''; ?>" href="/ecommerce/admin/admin_categories.php">
                <i class="bi bi-tags"></i> Categories
            </a>
            <a class="nav-link <?php echo $current_page === 'admin_users.php' ? 'active' : ''; ?>" href="/ecommerce/admin/admin_users.php">
                <i class="bi bi-people"></i> Users
            </a>
            <a class="nav-link <?php echo $current_page === 'admin_inventory.php' ? 'active' : ''; ?>" href="/ecommerce/admin/admin_inventory.php">
                <i class="bi bi-clipboard-data"></i> Inventory
            </a>
            <a class="nav-link" href="/ecommerce/logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
            <a class="nav-link back-to-site" href="/ecommerce/index.php">
                <i class="bi bi-house-door"></i> Back to Homepage
            </a>
        </nav>
    </div>

    <div class="main-content"> 