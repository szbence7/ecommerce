<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/language.php';

header('Content-Type: application/json');

// Hibakereséshez
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_POST['product_id'])) {
        throw new Exception('Product ID is required');
    }

    $product_id = (int)$_POST['product_id'];

    // Ellenőrizzük, hogy létezik-e a termék
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception('Product not found');
    }

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

    // Get cart total
    $cartTotal = 0;
    if (!empty($_SESSION['cart'])) {
        $productIds = array_keys($_SESSION['cart']);
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $products = $stmt->fetchAll();
        
        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            $price = ($product['is_on_sale'] && $product['discount_price'] !== null) ? $product['discount_price'] : $product['price'];
            $cartTotal += $price * $quantity;
        }
    }

    echo json_encode([
        'success' => true,
        'cartCount' => (string)array_sum($_SESSION['cart']),
        'cartTotal' => formatPrice($cartTotal),
        'message' => __t('cart.added_success', 'shop')
    ]);

} catch (Exception $e) {
    error_log("Cart error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => __t('cart.added_error', 'shop'),
        'debug' => $e->getMessage()
    ]);
}
