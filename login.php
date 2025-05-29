<?php
session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/social_config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, email, password, role, email_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Remove email verification check
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Set is_admin based on role
                $_SESSION['is_admin'] = ($user['role'] === 'admin');
                
                // Get cart count
                $count_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM user_carts WHERE user_id = ?");
                $count_stmt->bind_param("i", $user['id']);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result()->fetch_assoc();
                $_SESSION['cart_count'] = $count_result['count'] ?? 0;
                
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Email not found";
        }
    } else {
        $error = "Database error";
    }
}

include 'includes/header.php';
?>

<style>
.login-container {
    max-width: 400px;
    margin: 50px auto;
    padding: 30px;
    background: linear-gradient(145deg, var(--sunset-darker), var(--sunset-dark));
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    border: 1px solid var(--sunset-orange);
}

.login-container h2 {
    color: var(--sunset-light);
    text-align: center;
    margin-bottom: 25px;
    text-shadow: 0 0 8px var(--sunset-glow);
}

.error-message {
    color: var(--sunset-red);
    background: rgba(255, 46, 99, 0.1);
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 3px solid var(--sunset-red);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: var(--sunset-light);
    margin-bottom: 8px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    background: rgba(22, 33, 62, 0.5);
    border: 1px solid var(--sunset-purple);
    border-radius: 4px;
    color: var(--sunset-text);
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--sunset-orange);
    box-shadow: 0 0 0 3px rgba(255, 123, 37, 0.3);
}

.btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
}

.btn:hover {
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
    box-shadow: 0 0 15px rgba(255, 46, 99, 0.5);
}

.login-container p {
    color: var(--sunset-light);
    text-align: center;
    margin-top: 20px;
}

.login-container a {
    color: var(--sunset-orange);
    text-decoration: none;
    transition: all 0.2s;
}

.login-container a:hover {
    color: var(--sunset-light);
    text-shadow: 0 0 8px var(--sunset-glow);
}

.social-login {
    margin-top: 20px;
    text-align: center;
    position: relative;
}

.social-login::before,
.social-login::after {
    content: "";
    display: block;
    width: 40%;
    height: 1px;
    background: var(--sunset-purple);
    position: absolute;
    top: 50%;
}

.social-login::before {
    left: 0;
}

.social-login::after {
    right: 0;
}

.social-login span {
    background: var(--sunset-darker);
    padding: 0 15px;
    color: var(--sunset-light);
    position: relative;
    z-index: 1;
}

.google-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 12px;
    background: #ffffff;
    color: #757575;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 15px;
    text-decoration: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.google-btn:hover {
    background: #2d3748;
    color: #ffffff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.google-btn img {
    width: 24px;
    height: 24px;
    margin-right: 10px;
    background: white;
    padding: 2px;
    border-radius: 50%;
}
</style>

<div class="container login-container">
    <h2>Login</h2>
    <?php 
    if (!empty($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; 
    
    if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required class="form-control">
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required class="form-control">
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
    
    <div class="social-login">
        <span>Or login with</span>
    </div>
    
    <div id="g_id_signin" style="width: 100%; margin-top: 15px; display: flex; justify-content: center;"></div>
    
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>

<!-- Add Google Identity Services script -->
<script src="https://accounts.google.com/gsi/client" async></script>
<script>
    const googleClientId = '<?php echo GOOGLE_CLIENT_ID; ?>';
    
    window.onload = function() {
        // Clear any existing Google Sign-In state
        if (window.google && window.google.accounts && window.google.accounts.id) {
            google.accounts.id.cancel();
            google.accounts.id.disableAutoSelect();
        }

        google.accounts.id.initialize({
            client_id: googleClientId,
            callback: handleCredentialResponse,
            auto_select: false,  // Disable auto-selection
            cancel_on_tap_outside: true
        });
        
        google.accounts.id.renderButton(
            document.getElementById('g_id_signin'),
            {
                type: 'standard',
                theme: 'filled_blue',
                size: 'large',
                text: 'signin_with',
                shape: 'rectangular',
                logo_alignment: 'center',
                width: 280,
                locale: 'en'
            }
        );
    }
</script>
<script src="js/google-signin.js"></script>

<?php include 'includes/footer.php'; ?>