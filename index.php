<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/header.php';
require_once 'includes/functions.php';
require_once 'includes/language.php';

// Check if database connection exists
if (!isset($pdo)) {
    die("Database connection not established!");
}

try {
    // Get current currency and exchange rate for price filter
    $currentCurrency = getShopCurrency();
    $rate = getExchangeRate($currentCurrency);

    // Kategóriák lekérése
    $stmt = $pdo->query('SELECT * FROM categories');
    $categories = $stmt->fetchAll();

    // Termékek lekérése
    $stmt = $pdo->query('SELECT * FROM products');
    $products = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="container">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php include 'includes/price-filter-widget.php'; ?>
            <h3><?= __t('categories.title') ?></h3>
            <ul class="list-group">
                <?php foreach ($categories as $category): ?>
                    <li class="list-group-item">
                        <a href="category.php?id=<?php echo $category['id']; ?>">
                            <?php 
                            // Get category translation if available
                            $translation = getEntityTranslation('category', $category['id'], 'name', $category['name']);
                            echo htmlspecialchars($translation);
                            ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <div class="row" id="productList">
                <?php
                $where = "1=1";
                $params = [];

                // Add price filter if set
                if (isset($_GET['min_price']) && isset($_GET['max_price'])) {
                    $minPrice = filter_var($_GET['min_price'], FILTER_VALIDATE_FLOAT);
                    $maxPrice = filter_var($_GET['max_price'], FILTER_VALIDATE_FLOAT);
                    
                    if ($minPrice !== false && $maxPrice !== false) {
                        // Convert filter prices from display currency to EUR for database query
                        if ($currentCurrency !== 'EUR') {
                            $minPrice = convertToEUR($minPrice, $currentCurrency);
                            $maxPrice = convertToEUR($maxPrice, $currentCurrency);
                            
                            // Add a small buffer to max price to handle rounding issues
                            $maxPrice *= 1.001;
                        }
                        
                        // Use ROUND to handle floating point precision
                        $where .= " AND ROUND(price, 2) BETWEEN :min_price AND :max_price";
                        $params[':min_price'] = round($minPrice, 2);
                        $params[':max_price'] = round($maxPrice, 2);
                    }
                }

                $sql = "SELECT * FROM products WHERE $where ORDER BY id DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $products = $stmt->fetchAll();

                foreach ($products as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <a href="product.php?id=<?= $product['id'] ?>">
                                <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     style="height: 200px; object-fit: cover;">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">
                                    <a href="product.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                                        <?php 
                                        // Get product translation if available
                                        $translation = getEntityTranslation('product', $product['id'], 'name', $product['name']);
                                        echo htmlspecialchars($translation);
                                        ?>
                                    </a>
                                </h5>
                                <p class="card-text flex-grow-1">
                                    <?php 
                                    // Get product translation if available
                                    $translation = getEntityTranslation('product', $product['id'], 'description', $product['description']);
                                    echo htmlspecialchars(substr($translation, 0, 100)) . '...'; 
                                    ?>
                                </p>
                                <div class="mt-auto">
                                    <?php if ($product['is_on_sale'] && $product['discount_price']): ?>
                                        <p class="card-text mb-2">
                                            <span class="text-muted text-decoration-line-through"><?= formatPrice($product['price']) ?></span>
                                            <span class="h5 text-danger ms-2"><?= formatPrice($product['discount_price']) ?></span>
                                        </p>
                                    <?php else: ?>
                                        <p class="card-text mb-2"><?= formatPrice($product['price']) ?></p>
                                    <?php endif; ?>
                                    <button onclick="addToCart(<?= $product['id'] ?>)" class="btn btn-primary">
                                        <?= __t('cart.add', 'shop') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
window.addEventListener('priceRangeChanged', function(e) {
    const minPrice = e.detail.minPrice;
    const maxPrice = e.detail.maxPrice;
    
    // Reload the page with new price range
    window.location.href = `?min_price=${minPrice}&max_price=${maxPrice}`;
});

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
            
            // Frissítjük a kosár tartalmát
            const cartDrawer = document.getElementById('cartDrawer');
            if (cartDrawer) {
                updateCartContents();
            }
            
            // Show success message
            showAlert('success', data.message);
        } else {
            console.error('Server error:', data.debug);
            showAlert('danger', 'Error adding item to cart');
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        showAlert('danger', 'Error adding item to cart');
    });
}
</script>