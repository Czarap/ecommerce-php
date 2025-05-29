<?php
include 'includes/config.php';

header('Content-Type: application/json');

if (isset($_GET['query']) && !empty($_GET['query'])) {
    $query = '%' . $conn->real_escape_string($_GET['query']) . '%';
    
    $stmt = $conn->prepare("SELECT id, name FROM products WHERE name LIKE ? OR description LIKE ? LIMIT 5");
    $stmt->bind_param("ss", $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row;
    }
    
    echo json_encode($suggestions);
} else {
    echo json_encode([]);
}