<?php
include 'includes/header.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found");
}

// Get initial cart quantity
$currentQuantity = isset($_SESSION['cart'][$product['id']]) ? $_SESSION['cart'][$product['id']] : 1;
?>

<div class="row">
    <div class="col-md-6">
        <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop" class="img-fluid" alt="<?= htmlspecialchars($product['name']) ?>">
    </div>
    <div class="col-md-6">
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <p class="lead"><?= formatPrice($product['price']) ?></p>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        
        <div class="d-flex align-items-center gap-3 mb-3">
            <button onclick="updateQuantity('decrease')" class="btn btn-outline-secondary">-</button>
            <span id="quantity" class="fs-5"><?= $currentQuantity ?></span>
            <button onclick="updateQuantity('increase')" class="btn btn-outline-secondary">+</button>
        </div>

        <button onclick="updateCartToQuantity(<?= $product['id'] ?>, quantity)" class="btn btn-lg btn-success">Update Cart</button>
    </div>
</div>

<script>
let quantity = <?= $currentQuantity ?>;
const currentProductId = <?= $product['id'] ?>;
let cartQuantity = quantity;

// Listen for cart updates
window.addEventListener('cartUpdated', function(e) {
    const productInCart = e.detail.products.find(p => p.id === currentProductId);
    if (productInCart) {
        cartQuantity = productInCart.quantity;
        quantity = cartQuantity;
        document.getElementById('quantity').textContent = quantity;
    } else {
        cartQuantity = 0;
        quantity = 1;
        document.getElementById('quantity').textContent = quantity;
    }
});

function updateQuantity(action) {
    if (action === 'increase') {
        quantity++;
    } else if (action === 'decrease' && quantity > 1) {
        quantity--;
    }
    document.getElementById('quantity').textContent = quantity;
}

function updateCartToQuantity(productId, targetQuantity) {
    const action = targetQuantity > cartQuantity ? 'increase' : 'decrease';
    const iterations = Math.abs(targetQuantity - cartQuantity);
    
    let promises = [];
    for(let i = 0; i < iterations; i++) {
        promises.push(
            fetch('update_cart_quantity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&action=' + action
            }).then(response => response.json())
        );
    }
    
    Promise.all(promises)
        .then(results => {
            // Use the last result for the final update
            const lastResult = results[results.length - 1];
            if (lastResult.success) {
                // Update cart count in navbar with animation
                const cartBadge = document.getElementById('cart-count');
                if (cartBadge) {
                    cartBadge.textContent = lastResult.cartCount;
                    // Remove existing animation class if exists
                    cartBadge.classList.remove('cart-badge-pop');
                    // Trigger reflow to restart animation
                    void cartBadge.offsetWidth;
                    // Add animation class
                    cartBadge.classList.add('cart-badge-pop');
                }
                
                // Update cart drawer
                updateCartDrawer();
                
                // Update cartQuantity to match the new quantity
                cartQuantity = targetQuantity;
                
                // Show success message
                showAlert('success', lastResult.message || 'Cart updated successfully');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Error updating cart');
        });
}
</script>

<?php include 'includes/footer.php'; ?>