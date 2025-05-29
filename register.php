<?php
// Start session at the very top
session_start();

$page_title = "Register";
include 'includes/config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords don't match";

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email already exists";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, email_verified) VALUES (?, ?, ?, 'user', 1)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Registration successful! You can now login.";
            header("Location: login.php");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}

// Now include header after potential redirect
include 'includes/header.php';
?>

<style>
.register-container {
    max-width: 500px;
    margin: 50px auto;
    padding: 30px;
    background: linear-gradient(145deg, var(--sunset-darker), var(--sunset-dark));
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    border: 1px solid var(--sunset-orange);
}

.register-container h2 {
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

.success-message {
    color: var(--sunset-light);
    background: rgba(138, 43, 226, 0.1);
    padding: 15px;
    border-radius: 4px;
    text-align: center;
    border-left: 3px solid var(--sunset-purple);
}

.register-container input {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 15px;
    background: rgba(22, 33, 62, 0.5);
    border: 1px solid var(--sunset-purple);
    border-radius: 4px;
    color: var(--sunset-text);
    transition: all 0.3s;
}

.register-container input:focus {
    outline: none;
    border-color: var(--sunset-orange);
    box-shadow: 0 0 0 3px rgba(255, 123, 37, 0.3);
}

.register-container .btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.register-container .btn:hover {
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
    box-shadow: 0 0 15px rgba(255, 46, 99, 0.5);
}

.register-container p {
    color: var(--sunset-light);
    text-align: center;
    margin-top: 20px;
}

.register-container a {
    color: var(--sunset-orange);
    text-decoration: none;
    transition: all 0.2s;
}

.register-container a:hover {
    color: var(--sunset-light);
    text-shadow: 0 0 8px var(--sunset-glow);
}

.password-toggle {
    position: relative;
}

.password-toggle-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: var(--sunset-light);
}
</style>

<div class="container register-container">
    <h2>Create Account</h2>
    
    <?php if(!empty($errors)): ?>
        <div class="error-message">
            <?php foreach($errors as $msg): ?>
                <p><?= $msg ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required
               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
        
        <input type="email" name="email" placeholder="Email" required
               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        
        <div class="password-toggle">
            <input type="password" name="password" placeholder="Password" required id="password">
            <span class="password-toggle-icon" onclick="togglePassword('password')">
                <i class="fas fa-eye"></i>
            </span>
        </div>
        
        <div class="password-toggle">
            <input type="password" name="confirm_password" placeholder="Confirm Password" required id="confirm_password">
            <span class="password-toggle-icon" onclick="togglePassword('confirm_password')">
                <i class="fas fa-eye"></i>
            </span>
        </div>
        
        <button type="submit" class="btn">Register</button>
        
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </form>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = input.nextElementSibling.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php include 'includes/footer.php'; ?>