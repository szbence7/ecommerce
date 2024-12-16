<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_POST['product_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false]);
    exit;
}

$product_id = (int)$_POST['product_id'];
$action = $_POST['action'];

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($action === 'increase') {
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
} else if ($action === 'decrease') {
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]--;
        if ($_SESSION['cart'][$product_id] <= 0) {
            unset($_SESSION['cart'][$product_id]);
        }
    }
}

$cartCount = array_sum($_SESSION['cart']);

echo json_encode([
    'success' => true,
    'cartCount' => $cartCount
]);
