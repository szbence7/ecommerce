<?php
include 'includes/header.php';
require_once 'includes/functions.php';

if (empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit;
}

$total = 0;
$products = [];

// Get cart items
$ids = array_keys($_SESSION['cart']);
$placeholders = str_repeat('?,', count($ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$cart_products = $stmt->fetchAll();

foreach ($cart_products as $product) {
    $qty = $_SESSION['cart'][$product['id']];
    $subtotal = $product['price'] * $qty;
    $total += $subtotal;
    $products[] = [
        'id' => $product['id'],
        'qty' => $qty,
        'price' => $product['price']
    ];
}

// Process order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $order_id = $pdo->lastInsertId();
        
        // Insert order items
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($products as $product) {
            $stmt->execute([$order_id, $product['id'], $product['qty'], $product['price']]);
        }
        
        $pdo->commit();
        $_SESSION['cart'] = [];
        header('Location: order-confirmation.php?id=' . $order_id);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Order processing failed. Please try again.";
    }
}

// HTML részek maradnak ugyanazok
?> 