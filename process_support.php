<?php
session_start();
include 'includes/config.php';

header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Validate required fields
$required_fields = ['name', 'email', 'subject', 'message'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please fill in all required fields'
        ]);
        exit;
    }
}

// Sanitize input
$name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
$subject = filter_var(trim($_POST['subject']), FILTER_SANITIZE_STRING);
$message = filter_var(trim($_POST['message']), FILTER_SANITIZE_STRING);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter a valid email address'
    ]);
    exit;
}

try {
    // Insert into support_tickets table
    $stmt = $conn->prepare("INSERT INTO support_tickets (name, email, subject, message, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    
    if ($stmt->execute()) {
        // Send email notification (you can implement this later)
        // sendSupportNotification($email, $name, $subject);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Your message has been sent successfully'
        ]);
    } else {
        throw new Exception("Failed to save support ticket");
    }
} catch (Exception $e) {
    error_log("Support form error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while processing your request. Please try again later.'
    ]);
}
?> 