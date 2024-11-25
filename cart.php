<?php
include 'includes/header.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? '';
$response = ['success' => false];

switch ($action) {
    case 'get':
        $html = '';
        $total = 0;
        
        if (!empty($_SESSION['cart'])) {
            $ids = array_keys($_SESSION['cart']);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $products = $stmt->fetchAll();
            
            foreach ($products as $product) {
                $qty = $_SESSION['cart'][$product['id']];
                $subtotal = $product['price'] * $qty;
                $total += $subtotal;
                
                $html .= "<div class='cart-item mb-2'>";
                $html .= "<div class='d-flex justify-content-between'>";
                $html .= "<div>" . htmlspecialchars($product['name']) . "</div>";
                $html .= "<div>" . formatPrice($product['price']) . " x ";
                $html .= "<input type='number' min='1' value='$qty' onchange='updateCart({$product['id']}, this.value)' style='width: 60px'>";
                $html .= "</div></div></div>";
            }
        }
        
        $response = [
            'success' => true,
            'html' => $html,
            'total' => formatPrice($total),
            'count' => array_sum($_SESSION['cart'])
        ];
        break;

    case 'add':
        $id = (int)$_GET['id'];
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = 0;
        }
        $_SESSION['cart'][$id]++;
        $response = [
            'success' => true,
            'count' => array_sum($_SESSION['cart'])
        ];
        break;

    case 'remove':
        $id = (int)$_GET['id'];
        if (isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
        }
        $response = [
            'success' => true,
            'count' => array_sum($_SESSION['cart'])
        ];
        break;

    case 'update':
        $id = (int)$_GET['id'];
        $qty = (int)$_GET['qty'];
        if ($qty > 0) {
            $_SESSION['cart'][$id] = $qty;
        } else {
            unset($_SESSION['cart'][$id]);
        }
        $response = [
            'success' => true,
            'count' => array_sum($_SESSION['cart'])
        ];
        break;
}

echo json_encode($response); 