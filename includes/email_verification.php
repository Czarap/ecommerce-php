<?php
// Remove Composer dependency for now
class EmailVerification {
    private $sender_email;
    
    public function __construct($api_key, $sender_email) {
        $this->sender_email = $sender_email;
    }
    
    public function generateVerificationCode() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    public function sendVerificationEmail($to_email, $verification_code) {
        $subject = "Verify Your Email Address";
        
        $email_content = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #ff7b25;'>Welcome to E-Czar Shop!</h2>
                <p>Thank you for registering. Please use the verification code below to complete your registration:</p>
                <div style='background: #f8f9fa; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px; margin: 20px 0;'>
                    <strong>{$verification_code}</strong>
                </div>
                <p>This code will expire in 15 minutes.</p>
                <p>If you didn't request this verification, please ignore this email.</p>
                <hr>
                <p style='font-size: 12px; color: #666;'>This is an automated message, please do not reply.</p>
            </div>
        ";
        
        // Headers for HTML email
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $this->sender_email . "\r\n";
        $headers .= "Reply-To: " . $this->sender_email . "\r\n";
        
        // Send email using PHP mail function
        $mail_sent = mail($to_email, $subject, $email_content, $headers);
        
        return [
            'success' => $mail_sent,
            'message' => $mail_sent ? 'Verification email sent successfully' : 'Failed to send verification email'
        ];
    }
    
    public function storeVerificationCode($user_id, $code, $conn) {
        // Delete any existing verification codes for this user
        $stmt = $conn->prepare("DELETE FROM verification_codes WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Insert new verification code
        $stmt = $conn->prepare("
            INSERT INTO verification_codes (user_id, code, expires_at) 
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
        ");
        $stmt->bind_param("is", $user_id, $code);
        return $stmt->execute();
    }
    
    public function verifyCode($user_id, $code, $conn) {
        $stmt = $conn->prepare("
            SELECT * FROM verification_codes 
            WHERE user_id = ? AND code = ? AND expires_at > NOW() 
            AND used = 0
        ");
        $stmt->bind_param("is", $user_id, $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Mark code as used
            $stmt = $conn->prepare("
                UPDATE verification_codes 
                SET used = 1 
                WHERE user_id = ? AND code = ?
            ");
            $stmt->bind_param("is", $user_id, $code);
            $stmt->execute();
            
            // Mark user as verified
            $stmt = $conn->prepare("
                UPDATE users 
                SET email_verified = 1 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            return true;
        }
        
        return false;
    }
}
?> 