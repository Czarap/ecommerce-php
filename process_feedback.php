<?php
session_start();
require_once 'includes/config.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $feedback = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Validate the data
    if (!$rating || $rating < 1 || $rating > 5) {
        $_SESSION['feedback_error'] = "Please provide a valid rating.";
        header('Location: feedback.php');
        exit;
    }
    
    if (empty($subject) || empty($feedback)) {
        $_SESSION['feedback_error'] = "Please fill in all required fields.";
        header('Location: feedback.php');
        exit;
    }
    
    if (!$user_id && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['feedback_error'] = "Please provide a valid email address.";
        header('Location: feedback.php');
        exit;
    }
    
    try {
        // Prepare the SQL statement
        $sql = "INSERT INTO feedback (user_id, email, subject, feedback_text, rating, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssi", 
            $user_id,
            $email,
            $subject,
            $feedback,
            $rating
        );
        
        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['feedback_success'] = "Thank you for your feedback! We appreciate your input.";
            
            // Send email notification to admin (optional)
            $to = "admin@yourdomain.com"; // Replace with actual admin email
            $subject = "New Feedback Received";
            $message = "New feedback received:\n\n";
            $message .= "Rating: " . $rating . " stars\n";
            $message .= "Subject: " . $subject . "\n";
            $message .= "Feedback: " . $feedback . "\n";
            $message .= "From: " . ($user_id ? "User ID: " . $user_id : "Email: " . $email);
            
            $headers = "From: noreply@yourdomain.com"; // Replace with your domain
            
            // Uncomment to enable email notification
            // mail($to, $subject, $message, $headers);
            
        } else {
            throw new Exception("Error saving feedback");
        }
        
    } catch (Exception $e) {
        $_SESSION['feedback_error'] = "Sorry, there was an error submitting your feedback. Please try again later.";
        // Log the error for debugging
        error_log("Feedback submission error: " . $e->getMessage());
    }
    
} else {
    $_SESSION['feedback_error'] = "Invalid request method.";
}

// Redirect back to the feedback page
header('Location: feedback.php');
exit;
?> 