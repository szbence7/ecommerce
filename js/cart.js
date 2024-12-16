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
            // Update cart count in navbar
            document.getElementById('cart-count').textContent = '(' + data.cartCount + ')';
            
            // Update cart drawer
            updateCartDrawer();
        }
    })
    .catch(error => {
        console.error('Error:', error);
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
