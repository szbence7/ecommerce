<?php
$cartTotal = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $cartProducts = $stmt->fetchAll();
}
?>
<div id="cartDrawer" style="position: fixed; top: 0; right: -300px; width: 300px; height: 100vh; background: white; box-shadow: -2px 0 5px rgba(0,0,0,0.1); transition: 0.3s; z-index: 1000; overflow-y: auto;">
    <div class="p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Your Cart</h5>
            <button onclick="toggleCart()" class="btn-close"></button>
        </div>
        <div id="cartItems">
            <?php if (isset($cartProducts) && !empty($cartProducts)): ?>
                <?php foreach ($cartProducts as $product): ?>
                    <?php 
                        $quantity = $_SESSION['cart'][$product['id']];
                        $subtotal = $product['price'] * $quantity;
                        $cartTotal += $subtotal;
                    ?>
                    <div class="cart-item mb-3 border-bottom pb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0"><?= htmlspecialchars($product['name']) ?></h6>
                                <div class="d-flex align-items-center mt-2">
                                    <button onclick="updateCartQuantity(<?= $product['id'] ?>, 'decrease')" class="btn btn-sm btn-outline-secondary">-</button>
                                    <span class="mx-2 quantity-<?= $product['id'] ?>"><?= $quantity ?></span>
                                    <button onclick="updateCartQuantity(<?= $product['id'] ?>, 'increase')" class="btn btn-sm btn-outline-secondary">+</button>
                                </div>
                            </div>
                            <div class="subtotal-<?= $product['id'] ?>">
                                <?= number_format($subtotal, 0, '.', ' ') ?> Ft
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">Your cart is empty</p>
            <?php endif; ?>
        </div>
        <div class="mt-3">
            <div class="d-flex justify-content-between mb-2">
                <strong>Total:</strong>
                <span id="cartTotal"><?= number_format($cartTotal, 0, '.', ' ') ?> Ft</span>
            </div>
            <div id="checkoutButtonContainer">
                <?php if (isset($cartProducts) && !empty($cartProducts)): ?>
                    <a href="checkout.php" class="btn btn-primary w-100">Checkout</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCart() {
    const drawer = document.getElementById('cartDrawer');
    if(drawer.style.right === '0px') {
        drawer.style.right = '-300px';
    } else {
        drawer.style.right = '0px';
    }
}

function updateCartQuantity(productId, action) {
    fetch('update_cart_quantity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&action=' + action
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in navbar
            document.getElementById('cart-count').textContent = '(' + data.cartCount + ')';
            
            // Update cart contents
            updateCartContents();
        }
    });
}

function updateCartContents() {
    fetch('get_cart_html.php')
        .then(response => response.json())
        .then(data => {
            // Update cart items
            document.getElementById('cartItems').innerHTML = data.html;
            
            // Update total
            document.getElementById('cartTotal').innerHTML = data.total;
            
            // Update checkout button
            document.getElementById('checkoutButtonContainer').innerHTML = data.checkoutButton;
            
            // Update cart count
            document.getElementById('cart-count').textContent = '(' + data.cartCount + ')';
        });
}
</script>