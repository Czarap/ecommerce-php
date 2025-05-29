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

$host = 'localhost';
$user = 'root';
$pass = 'YES';
$db = 'ecommerce';

try {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set the character set to utf8mb4
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting character set: " . $conn->error);
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

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