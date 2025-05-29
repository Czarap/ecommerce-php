<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/social_config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify state to prevent CSRF
if (!isset($_GET['state']) || !isset($_SESSION['google_state']) || $_GET['state'] !== $_SESSION['google_state']) {
    error_log('Invalid state parameter');
    $_SESSION['error'] = 'Invalid state parameter. Please try again.';
    header('Location: login.php');
    exit();
}

// Clear the state from session
unset($_SESSION['google_state']);

// Check for errors
if (isset($_GET['error'])) {
    error_log('Google returned error: ' . $_GET['error']);
    $_SESSION['error'] = 'Google login failed: ' . $_GET['error'];
    header('Location: login.php');
    exit();
}

// Verify authorization code
if (!isset($_GET['code'])) {
    error_log('No authorization code received');
    $_SESSION['error'] = 'Authorization failed. Please try again.';
    header('Location: login.php');
    exit();
}

try {
    // Token endpoint request
    $ch = curl_init('https://oauth2.googleapis.com/token');
    
    $token_request = [
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($token_request),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Token request failed: ' . curl_error($ch));
    }
    
    $token_data = json_decode($response, true);
    
    if (!isset($token_data['access_token'])) {
        error_log('Token response: ' . $response);
        throw new Exception('Failed to get access token');
    }

    // Get user profile
    $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token_data['access_token'],
            'Accept: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Profile request failed: ' . curl_error($ch));
    }
    
    $user_data = json_decode($response, true);
    
    if (!isset($user_data['id'], $user_data['email'])) {
        error_log('User data response: ' . $response);
        throw new Exception('Failed to get user profile data');
    }

    // Process user data
    $google_id = $user_data['id'];
    $email = $user_data['email'];
    $name = $user_data['name'] ?? explode('@', $email)[0];

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE social_id = ? OR email = ?");
    $stmt->bind_param("ss", $google_id, $email);
    
    if (!$stmt->execute()) {
        throw new Exception('Database query failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing user
        $user = $result->fetch_assoc();
        
        $update = $conn->prepare("UPDATE users SET social_id = ?, name = ?, social_provider = 'google' WHERE id = ?");
        $update->bind_param("ssi", $google_id, $name, $user['id']);
        
        if (!$update->execute()) {
            throw new Exception('Failed to update user: ' . $update->error);
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['is_admin'] = ($user['role'] === 'admin');
    } else {
        // Create new user
        $stmt = $conn->prepare("INSERT INTO users (email, name, social_id, social_provider, role, email_verified) VALUES (?, ?, ?, 'google', 'user', 1)");
        $stmt->bind_param("sss", $email, $name, $google_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create user: ' . $stmt->error);
        }
        
        $user_id = $stmt->insert_id;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['user_role'] = 'user';
        $_SESSION['is_admin'] = false;
    }

    // Get cart count
    $count_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM user_carts WHERE user_id = ?");
    $count_stmt->bind_param("i", $_SESSION['user_id']);
    
    if (!$count_stmt->execute()) {
        error_log('Failed to get cart count: ' . $count_stmt->error);
    } else {
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $_SESSION['cart_count'] = $count_result['count'] ?? 0;
    }

    // Redirect to home page
    header('Location: index.php');
    exit();

} catch (Exception $e) {
    error_log('Google login error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred during login. Please try again.';
    header('Location: login.php');
    exit();
}
?> 