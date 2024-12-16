<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$cartTotal = 0;
$cartCount = 0;
$cartProducts = [];

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();

    $html = '';
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        $cartTotal += $subtotal;
        $cartCount += $quantity;

        $cartProducts[] = [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity
        ];

        $html .= '<div class="cart-item mb-3 border-bottom pb-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">' . htmlspecialchars($product['name']) . '</h6>
                    <div class="d-flex align-items-center mt-2">
                        <button onclick="updateCartQuantity(' . $product['id'] . ', \'decrease\')" class="btn btn-sm btn-outline-secondary">-</button>
                        <span class="mx-2 quantity-' . $product['id'] . '">' . $quantity . '</span>
                        <button onclick="updateCartQuantity(' . $product['id'] . ', \'increase\')" class="btn btn-sm btn-outline-secondary">+</button>
                    </div>
                </div>
                <div class="subtotal-' . $product['id'] . '">
                    ' . number_format($subtotal, 0, '.', ' ') . ' Ft
                </div>
            </div>
        </div>';
    }
} else {
    $html = '<p class="text-center">Your cart is empty</p>';
}

$checkoutButton = '';
if ($cartCount > 0) {
    $checkoutButton = '<a href="checkout.php" class="btn btn-primary w-100">Checkout</a>';
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'html' => $html,
    'total' => number_format($cartTotal, 0, '.', ' ') . ' Ft',
    'cartCount' => $cartCount,
    'checkoutButton' => $checkoutButton,
    'products' => $cartProducts
]);
?>
