<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

$term = isset($_GET['q']) ? trim($_GET['q']) : '';
$response = [];

try {
    $sql = "SELECT * FROM products WHERE name LIKE :term LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $searchTerm = '%' . $term . '%';
    $stmt->bindParam(':term', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    
    while ($product = $stmt->fetch()) {
        $response[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => 'images/placeholder.jpg'
        ];
    }
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    $response = ['error' => true, 'message' => $e->getMessage()];
}

echo json_encode($response);