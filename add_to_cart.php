<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$product_id = (int)$_POST['product_id'];

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add product to cart
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]++;
} else {
    $_SESSION['cart'][$product_id] = 1;
}

// Get total number of items in cart
$cartCount = array_sum($_SESSION['cart']);

echo json_encode([
    'success' => true,
    'cartCount' => $cartCount,
    'message' => 'Product added to cart successfully'
]);
