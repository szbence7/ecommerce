<?php
include 'includes/db.php';

$term = $_GET['q'] ?? '';
$response = [];

if (strlen($term) >= 3) {
    $sql = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ? LIMIT 5";
    $stmt = mysqli_prepare($conn, $sql);
    $searchTerm = "%$term%";
    mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $searchTerm);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($product = mysqli_fetch_assoc($result)) {
        $response[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'url' => "product.php?id=" . $product['id']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response); 