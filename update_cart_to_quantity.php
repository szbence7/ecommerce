<?php
session_start();
require_once 'includes/db.php';

$response = ['success' => false];

if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity > 0) {
        $_SESSION['cart'][$product_id] = $quantity;
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
    
    $response = [
        'success' => true,
        'cartCount' => isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
