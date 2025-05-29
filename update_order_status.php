<?php
session_start();
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

$order_id = intval($_GET['id']);
$status = in_array($_GET['status'], ['pending', 'completed', 'cancelled']) ? $_GET['status'] : 'pending';

$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $order_id);
$stmt->execute();

header("Location: admin.php?tab=orders");
exit();