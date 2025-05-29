<?php
session_start();
require_once 'includes/config.php';

echo "<h2>Database User Record:</h2>";
$stmt = $conn->prepare("SELECT id, name, email, role, is_admin FROM users WHERE id = 2");
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
echo "<pre>";
print_r($user);
echo "</pre>";

echo "<h2>Current Session Variables:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Try to update both fields again
$stmt = $conn->prepare("UPDATE users SET role = 'admin', is_admin = 1 WHERE id = 2");
$stmt->execute();

echo "<h2>After Update - Database User Record:</h2>";
$stmt = $conn->prepare("SELECT id, name, email, role, is_admin FROM users WHERE id = 2");
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
echo "<pre>";
print_r($user);
echo "</pre>";
?> 