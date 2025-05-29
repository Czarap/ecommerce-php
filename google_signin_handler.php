<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/social_config.php';

try {
    // Get POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    if (!$data || !isset($data->credential)) {
        throw new Exception('Invalid request');
    }

    // Decode the JWT token (header.payload.signature)
    $jwt = $data->credential;
    $parts = explode('.', $jwt);
    if (count($parts) != 3) {
        throw new Exception('Invalid token format');
    }

    // Decode the payload
    $payload = json_decode(base64_decode($parts[1]), true);
    if (!$payload) {
        throw new Exception('Invalid token payload');
    }

    // Verify token is not expired
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        throw new Exception('Token has expired');
    }

    // Verify the audience matches our client ID
    if (!isset($payload['aud']) || $payload['aud'] !== GOOGLE_CLIENT_ID) {
        throw new Exception('Invalid token audience');
    }

    // Extract user information
    $google_id = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'];
    $picture = $payload['picture'];

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE social_id = ? OR email = ?");
    $stmt->bind_param("ss", $google_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists - update their information
        $user = $result->fetch_assoc();
        $stmt = $conn->prepare("UPDATE users SET social_id = ?, name = ?, social_provider = 'google' WHERE id = ?");
        $stmt->bind_param("ssi", $google_id, $name, $user['id']);
        $stmt->execute();
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['is_admin'] = ($user['role'] === 'admin');
        
        // Get cart count
        $count_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM user_carts WHERE user_id = ?");
        $count_stmt->bind_param("i", $user['id']);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $_SESSION['cart_count'] = $count_result['count'] ?? 0;
        
        echo json_encode(['success' => true, 'message' => 'Login successful']);
    } else {
        // Create new user
        $stmt = $conn->prepare("INSERT INTO users (email, name, social_id, social_provider, role, email_verified) VALUES (?, ?, ?, 'google', 'user', 1)");
        $stmt->bind_param("sss", $email, $name, $google_id);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['user_role'] = 'user';
            $_SESSION['is_admin'] = false;
            $_SESSION['cart_count'] = 0;
            
            echo json_encode(['success' => true, 'message' => 'Registration successful']);
        } else {
            throw new Exception('Failed to create user: ' . $conn->error);
        }
    }
} catch (Exception $e) {
    error_log('Google Sign-In Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
?> 