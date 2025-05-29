<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'read_and_close'  => false,
        'cookie_path' => '/',
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true
    ]);
}

// Get the base URL for the application
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    return rtrim($protocol . $host . $path, '/');
}

// Database configuration
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USERNAME') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_DATABASE') ?: 'ecommerce';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Connection failed. Please try again later.");
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Define base URL
$base_url = isset($_SERVER['VERCEL_URL']) 
    ? 'https://' . $_SERVER['VERCEL_URL']
    : 'http://localhost/ecommerce';

// Define constants
define('BASE_URL', $base_url);
define('UPLOADS_DIR', __DIR__ . '/../uploads');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === 'true' ? '1' : '0');

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', '1');
}

// Set timezone
date_default_timezone_set('Asia/Manila');

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if (isset($_SESSION['user_id']) && !isset($_SESSION['cart_count'])) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM user_carts WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $_SESSION['cart_count'] = $result['count'] ?? 0;
}

function updateCartCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM user_carts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $_SESSION['cart_count'] = $result['count'] ?? 0;
    return $_SESSION['cart_count'];
}

// Add this at the top of header.php, after session_start()
// if (isset($_SESSION['user_id'])) {
//     echo '<div style="background: #ff0; color: #000; padding: 10px; margin: 10px;">';
//     echo 'Debug Info:<br>';
//     echo 'User ID: ' . $_SESSION['user_id'] . '<br>';
//     echo 'User Role: ' . ($_SESSION['user_role'] ?? 'not set') . '<br>';
//     echo 'Is Admin: ' . (isset($_SESSION['is_admin']) ? var_export($_SESSION['is_admin'], true) : 'not set') . '<br>';
//     echo '</div>';
// }
?>