<?php
include 'includes/header.php';

try {
    // Get the number of products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "Number of products in database: " . $result['count'] . "<br><br>";

    // Show all products
    $stmt = $pdo->query("SELECT * FROM products");
    $products = $stmt->fetchAll();
    
    echo "<pre>";
    print_r($products);
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
