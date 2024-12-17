<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/language.php';

$response = [
    'success' => false,
    'cartCount' => 0,
    'message' => '',
];

if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Set the quantity directly
    if ($quantity > 0) {
        $_SESSION['cart'][$product_id] = $quantity;
    } else {
        // If quantity is 0 or less, remove from cart
        unset($_SESSION['cart'][$product_id]);
    }
    
    $response['success'] = true;
    $response['cartCount'] = array_sum($_SESSION['cart']);
    $response['message'] = __t('cart.updated');
}

header('Content-Type: application/json');
echo json_encode($response);
