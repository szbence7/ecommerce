<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/language.php';

$response = [
    'cartItems' => '',
    'cartTotal' => '',
    'showCheckoutButton' => false
];

$cartTotal = 0;

// Get current currency and exchange rate
$currentCurrency = getShopCurrency();
$rate = getExchangeRate($currentCurrency);

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    
    // Get products with translations
    $stmt = $pdo->prepare("
        SELECT p.*, pt.name as translated_name, pt.description as translated_description 
        FROM products p 
        LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_code = ?
        WHERE p.id IN ($placeholders)
    ");
    $params = array_merge([getLanguageCode()], $productIds);
    $stmt->execute($params);
    $cartProducts = $stmt->fetchAll();

    $cartHtml = '';
    foreach ($cartProducts as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $price = $product['price'] * $rate;
        $subtotal = $price * $quantity;
        $cartTotal += $subtotal;

        $cartHtml .= '
        <div class="cart-item mb-3 border-bottom pb-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">' . htmlspecialchars($product['translated_name'] ?? $product['name']) . '</h6>
                    <div class="d-flex align-items-center mt-2">
                        <button onclick="updateCartQuantity(' . $product['id'] . ', \'decrease\')" class="btn btn-sm btn-outline-secondary">-</button>
                        <span class="mx-2 quantity-' . $product['id'] . '">' . $quantity . '</span>
                        <button onclick="updateCartQuantity(' . $product['id'] . ', \'increase\')" class="btn btn-sm btn-outline-secondary">+</button>
                    </div>
                </div>
                <div class="subtotal-' . $product['id'] . '">
                    ' . formatPrice($subtotal) . '
                </div>
            </div>
        </div>';
    }

    $response['cartItems'] = $cartHtml;
    $response['showCheckoutButton'] = true;
} else {
    $response['cartItems'] = '<p class="text-center">' . __t('cart.drawer.empty') . '</p>';
}

$response['cartTotal'] = formatPrice($cartTotal);

header('Content-Type: application/json');
echo json_encode($response);
