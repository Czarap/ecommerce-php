<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_id']) && 
           !empty($_SESSION['user_id']) &&
           isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'admin';
}

// Check if user is seller
function isSeller() {
    return isset($_SESSION['user_id']) && 
           !empty($_SESSION['user_id']) &&
           isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'seller';
}

// Function to restrict access to admin only
function adminOnly() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /ecommerce/login.php');
        exit();
    }
    
    if (!isAdmin()) {
        header('Location: /ecommerce/index.php');
        exit();
    }
}

// Function to restrict access to sellers only
function sellerOnly() {
    if (!isLoggedIn() || !isSeller()) {
        header('Location: /ecommerce/login.php');
        exit();
    }
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /ecommerce/login.php');
        exit();
    }
}

// Function to check if user has specific role
function hasRole($role) {
    switch($role) {
        case 'admin':
            return isAdmin();
        case 'seller':
            return isSeller();
        default:
            return false;
    }
}

// Function to check if user has any of the specified roles
function hasAnyRole($roles) {
    foreach ($roles as $role) {
        if (hasRole($role)) {
            return true;
        }
    }
    return false;
} 