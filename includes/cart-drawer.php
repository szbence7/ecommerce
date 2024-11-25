<div id="cartDrawer" style="position: fixed; top: 0; right: -300px; width: 300px; height: 100vh; background: white; box-shadow: -2px 0 5px rgba(0,0,0,0.1); transition: 0.3s; z-index: 1000;">
    <div class="p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Your Cart</h5>
            <button onclick="toggleCart()" class="btn-close"></button>
        </div>
        <div id="cartItems">
            <!-- Cart items will be loaded here via AJAX -->
        </div>
        <div class="mt-3">
            <div class="d-flex justify-content-between mb-2">
                <strong>Total:</strong>
                <span id="cartTotal">$0.00</span>
            </div>
            <a href="checkout.php" class="btn btn-primary w-100">Checkout</a>
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
        loadCart();
    }
}

function loadCart() {
    fetch('cart.php?action=get')
        .then(response => response.json())
        .then(data => {
            document.getElementById('cartItems').innerHTML = data.html;
            document.getElementById('cartTotal').innerText = '$' + data.total;
            document.getElementById('cartCount').innerText = data.count;
        });
}
</script> 