<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add or update quantity in cart
if ($quantity > 0) {
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
} else {
    // Remove item if quantity is 0
    unset($_SESSION['cart'][$product_id]);
}

// Get total items in cart
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

// Return success response
echo json_encode([
    'success' => true,
    'cartCount' => $cartCount,
    'message' => 'Cart updated successfully'
]);
