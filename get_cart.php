<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get cart items
$cartItems = [];
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        $cartItems[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $_SESSION['cart'][$product['id']],
            'total' => $product['price'] * $_SESSION['cart'][$product['id']]
        ];
    }
}

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['total'];
}

// Return HTML for cart drawer
?>
<div class="cart-items">
    <?php if (empty($cartItems)): ?>
        <p class="text-center">Your cart is empty</p>
    <?php else: ?>
        <?php foreach ($cartItems as $item): ?>
            <div class="cart-item">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6><?= htmlspecialchars($item['name']) ?></h6>
                        <small>Quantity: <?= $item['quantity'] ?></small>
                    </div>
                    <div>
                        <p><?= formatPrice($item['total']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="cart-total mt-3">
            <div class="d-flex justify-content-between">
                <h5>Total:</h5>
                <h5><?= formatPrice($total) ?></h5>
            </div>
        </div>
        
        <div class="cart-actions mt-3">
            <a href="checkout.php" class="btn btn-primary w-100">Checkout</a>
        </div>
    <?php endif; ?>
</div>
