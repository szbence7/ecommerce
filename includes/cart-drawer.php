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
<!-- Overlay -->
<div id="cartOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); opacity: 0; visibility: hidden; transition: 0.3s; z-index: 999;" onclick="closeCart()"></div>

<div id="cartDrawer" style="position: fixed; top: 0; right: -300px; width: 300px; height: 100vh; background: white; box-shadow: -2px 0 5px rgba(0,0,0,0.1); transition: 0.3s; z-index: 1000; overflow-y: auto;">
    <div class="p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Your Cart</h5>
            <button onclick="closeCart()" class="btn-close"></button>
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
    const overlay = document.getElementById('cartOverlay');
    
    if(drawer.style.right === '0px') {
        closeCart();
    } else {
        drawer.style.right = '0px';
        overlay.style.visibility = 'visible';
        overlay.style.opacity = '1';
        updateCartContents();
    }
}

function closeCart() {
    const drawer = document.getElementById('cartDrawer');
    const overlay = document.getElementById('cartOverlay');
    
    drawer.style.right = '-300px';
    overlay.style.opacity = '0';
    setTimeout(() => {
        overlay.style.visibility = 'hidden';
    }, 300);
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

            // Dispatch cart updated event
            window.dispatchEvent(new CustomEvent('cartUpdated', { 
                detail: { 
                    products: data.products,
                    cartCount: data.cartCount
                }
            }));
        });
}

// Close cart when pressing Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeCart();
    }
});
</script>
