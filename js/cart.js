function addToCart(productId) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in navbar with animation
            const cartBadge = document.getElementById('cart-count');
            if (cartBadge) {
                cartBadge.textContent = data.cartCount;
                // Remove existing animation class if exists
                cartBadge.classList.remove('cart-badge-pop');
                // Trigger reflow to restart animation
                void cartBadge.offsetWidth;
                // Add animation class
                cartBadge.classList.add('cart-badge-pop');
            }
            
            // Update cart drawer
            updateCartDrawer();
            
            // Show success message
            showAlert('success', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error adding item to cart');
    });
}

function updateCartDrawer() {
    fetch('get_cart.php')
        .then(response => response.text())
        .then(html => {
            const cartDrawer = document.getElementById('cart-drawer');
            if (cartDrawer) {
                cartDrawer.innerHTML = html;
            }
        });
}
