<?php
require_once 'functions.php';
require_once 'language.php';

$cartTotal = 0;
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
    $params = array_merge([getCurrentLanguage()], $productIds);
    $stmt->execute($params);
    $cartProducts = $stmt->fetchAll();
}

// Get current currency and exchange rate
$currentCurrency = getShopCurrency();
$rate = getExchangeRate($currentCurrency);
?>
<!-- Overlay -->
<div id="cartOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); opacity: 0; visibility: hidden; transition: 0.3s; z-index: 999;" onclick="closeCart()"></div>

<div id="cartDrawer" style="position: fixed; top: 0; right: -300px; width: 300px; height: 100vh; background: white; box-shadow: -2px 0 5px rgba(0,0,0,0.1); transition: 0.3s; z-index: 1000; overflow-y: auto;">
    <div class="p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><?= __t('cart.drawer.title') ?></h5>
            <button onclick="closeCart()" class="btn-close"></button>
        </div>
        <div id="cartItems">
            <?php if (isset($cartProducts) && !empty($cartProducts)): ?>
                <?php foreach ($cartProducts as $product): ?>
                    <?php 
                        $quantity = $_SESSION['cart'][$product['id']];
                        // Az ár már forintban van tárolva, nem kell átváltani
                        $price = $product['price'];
                        $subtotal = $price * $quantity;
                        $cartTotal += $subtotal;
                    ?>
                    <div class="cart-item mb-3 border-bottom pb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0"><?= htmlspecialchars($product['translated_name'] ?? $product['name']) ?></h6>
                                <div class="d-flex align-items-center mt-2">
                                    <button onclick="updateCartQuantity(<?= $product['id'] ?>, 'decrease')" class="btn btn-sm btn-outline-secondary">-</button>
                                    <span class="mx-2 quantity-<?= $product['id'] ?>"><?= $quantity ?></span>
                                    <button onclick="updateCartQuantity(<?= $product['id'] ?>, 'increase')" class="btn btn-sm btn-outline-secondary">+</button>
                                </div>
                            </div>
                            <div class="subtotal-<?= $product['id'] ?>">
                                <?= formatPrice($subtotal) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center"><?= __t('cart.drawer.empty') ?></p>
            <?php endif; ?>
        </div>
        <div class="mt-3">
            <div class="d-flex justify-content-between mb-2">
                <strong><?= __t('cart.drawer.total') ?></strong>
                <span id="cartTotal"><?= formatPrice($cartTotal) ?></span>
            </div>
            <div id="checkoutButtonContainer">
                <?php if (isset($cartProducts) && !empty($cartProducts)): ?>
                    <a href="checkout.php" class="btn btn-primary w-100"><?= __t('cart.drawer.checkout') ?></a>
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
    
    // Várjunk az animáció végéig, majd rejtsük el az overlay-t
    setTimeout(() => {
        overlay.style.visibility = 'hidden';
        document.body.style.overflow = 'auto'; // Visszaállítjuk az oldal görgetését
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
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = data.cartCount > 0 ? '(' + data.cartCount + ')' : '(0)';
            }
            
            if (data.cartCount === 0) {
                // Ha üres a kosár, zárjuk be a drawert
                closeCart();
            } else {
                // Update quantity display
                const quantityElement = document.querySelector('.quantity-' + productId);
                if (quantityElement) {
                    quantityElement.textContent = data.quantity;
                }
                
                // Update subtotal
                const subtotalElement = document.querySelector('.subtotal-' + productId);
                if (subtotalElement) {
                    subtotalElement.textContent = data.subtotal;
                }
                
                // Update cart total
                const cartTotalElement = document.getElementById('cartTotal');
                if (cartTotalElement) {
                    cartTotalElement.textContent = data.cartTotal;
                }

                // Update checkout button visibility
                const checkoutButtonContainer = document.getElementById('checkoutButtonContainer');
                if (checkoutButtonContainer) {
                    if (data.cartCount > 0) {
                        checkoutButtonContainer.innerHTML = '<a href="checkout.php" class="btn btn-primary w-100"><?= __t('cart.drawer.checkout') ?></a>';
                    } else {
                        checkoutButtonContainer.innerHTML = '';
                    }
                }
                
                // Ha a mennyiség 0, frissítsük a kosár tartalmát
                if (data.quantity === 0) {
                    updateCartContents();
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updateCartContents() {
    fetch('get_cart_html.php')
        .then(response => response.json())
        .then(data => {
            // Update cart items
            document.getElementById('cartItems').innerHTML = data.cartItems;
            document.getElementById('cartTotal').textContent = data.cartTotal;
            
            // Update checkout button
            const checkoutButtonContainer = document.getElementById('checkoutButtonContainer');
            if (data.showCheckoutButton) {
                checkoutButtonContainer.innerHTML = '<a href="checkout.php" class="btn btn-primary w-100"><?= __t('cart.drawer.checkout') ?></a>';
            } else {
                checkoutButtonContainer.innerHTML = '';
            }
        });
}
</script>
