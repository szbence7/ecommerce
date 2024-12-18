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

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-7">
            <div class="position-sticky" style="top: 2rem;">
                <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop" 
                     class="img-fluid rounded-3 w-100 object-fit-cover" style="max-height: 600px;" 
                     alt="<?= htmlspecialchars($product['name']) ?>">
            </div>
        </div>
        <div class="col-lg-5">
            <div class="product-details">
                <h1 class="display-5 fw-bold mb-4"><?= htmlspecialchars($product['name']) ?></h1>
                <p class="h2 text-primary mb-4"><?= formatPrice($product['price']) ?></p>
                <div class="mb-4">
                    <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>
                
                <div class="quantity-selector p-3 bg-light rounded-3 mb-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Quantity:</span>
                        <div class="d-flex align-items-center gap-3">
                            <button onclick="updateQuantity('decrease')" 
                                    class="btn btn-outline-dark rounded-circle d-flex align-items-center justify-content-center" 
                                    style="width: 38px; height: 38px;">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span id="quantity" class="fs-4 fw-semibold"><?= $currentQuantity ?></span>
                            <button onclick="updateQuantity('increase')" 
                                    class="btn btn-outline-dark rounded-circle d-flex align-items-center justify-content-center" 
                                    style="width: 38px; height: 38px;">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button onclick="updateCartToQuantity()" 
                        class="btn btn-primary w-100 py-3 rounded-3 text-uppercase fw-bold">
                    Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let quantity = <?= $currentQuantity ?>;
const currentProductId = <?= $product['id'] ?>;

function updateQuantity(action) {
    if (action === 'increase') {
        quantity++;
    } else if (action === 'decrease' && quantity > 1) {
        quantity--;
    }
    document.getElementById('quantity').textContent = quantity;
}

function updateCartToQuantity() {
    fetch('set_cart_quantity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + currentProductId + '&quantity=' + quantity
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in navbar with animation
            const cartBadge = document.getElementById('cart-count');
            if (cartBadge) {
                cartBadge.textContent = data.cartCount;
                cartBadge.classList.remove('cart-badge-pop');
                void cartBadge.offsetWidth;
                cartBadge.classList.add('cart-badge-pop');
            }
            
            // Update cart drawer
            updateCartDrawer();
            
            // Show success message
            showAlert('success', data.message || 'Cart updated successfully');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error updating cart');
    });
}
</script>

<?php include 'includes/footer.php'; ?>