<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/language.php';

$response = [
    'success' => false,
    'cartCount' => 0,
    'quantity' => 0,
    'subtotal' => '',
    'cartTotal' => ''
];

if (isset($_POST['product_id']) && isset($_POST['action'])) {
    $product_id = (int)$_POST['product_id'];
    $action = $_POST['action'];
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Get current quantity
    $current_quantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
    
    // Update quantity based on action
    if ($action === 'increase') {
        $_SESSION['cart'][$product_id] = $current_quantity + 1;
    } else if ($action === 'decrease') {
        if ($current_quantity > 1) {
            $_SESSION['cart'][$product_id] = $current_quantity - 1;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    
    // Get updated cart total
    $cartTotal = 0;
    if (!empty($_SESSION['cart'])) {
        $productIds = array_keys($_SESSION['cart']);
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $products = $stmt->fetchAll();
        
        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            // Az ár már forintban van tárolva
            $price = $product['price'];
            $cartTotal += $price * $quantity;
            
            // Calculate subtotal for the updated product
            if ($product['id'] == $product_id) {
                $response['quantity'] = $quantity;
                $response['subtotal'] = formatPrice($price * $quantity);
            }
        }
    }
    
    $response['success'] = true;
    $response['cartCount'] = array_sum($_SESSION['cart']);
    $response['cartTotal'] = formatPrice($cartTotal);
}

header('Content-Type: application/json');
echo json_encode($response);
