<?php
session_start();
require_once 'includes/db.php';

$cartTotal = 0;
$html = '';

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $cartProducts = $stmt->fetchAll();

    foreach ($cartProducts as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        $cartTotal += $subtotal;

        $html .= '<div class="cart-item mb-3 border-bottom pb-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">'. htmlspecialchars($product['name']) .'</h6>
                    <div class="d-flex align-items-center mt-2">
                        <button onclick="updateCartQuantity('. $product['id'] .', \'decrease\')" class="btn btn-sm btn-outline-secondary">-</button>
                        <span class="mx-2 quantity-'. $product['id'] .'">'. $quantity .'</span>
                        <button onclick="updateCartQuantity('. $product['id'] .', \'increase\')" class="btn btn-sm btn-outline-secondary">+</button>
                    </div>
                </div>
                <div class="subtotal-'. $product['id'] .'">
                    '. number_format($subtotal, 0, '.', ' ') .' Ft
                </div>
            </div>
        </div>';
    }
} else {
    $html = '<p class="text-center">Your cart is empty</p>';
}

$checkoutButton = '';
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $checkoutButton = '<a href="checkout.php" class="btn btn-primary w-100">Checkout</a>';
}

echo json_encode([
    'html' => $html,
    'total' => number_format($cartTotal, 0, '.', ' ') . ' Ft',
    'checkoutButton' => $checkoutButton,
    'cartCount' => array_sum($_SESSION['cart'])
]);
