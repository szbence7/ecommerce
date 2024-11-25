<?php
include 'layout/header.php';
require_once '../includes/functions.php';

// Single order view
if (isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    
    // Update order status
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $order_id]);
    }
    
    // Get order details
    $stmt = $pdo->prepare("SELECT o.*, u.name as customer 
                          FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if ($order) {
        // Get order items
        $stmt = $pdo->prepare("SELECT oi.*, p.name, p.image 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();
        
        // HTML részek maradnak ugyanazok, csak foreach használata
    }
}

// Get all orders
$stmt = $pdo->query("SELECT o.*, u.name as customer 
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id 
                     ORDER BY o.created_at DESC");
$orders = $stmt->fetchAll();
?>

<!-- HTML részek maradnak ugyanazok, csak foreach használata --> 