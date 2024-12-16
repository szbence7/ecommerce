<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/header.php';
require_once 'includes/functions.php';

// Check if database connection exists
if (!isset($pdo)) {
    die("Database connection not established!");
}

try {
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
            <h3>Categories</h3>
            <ul class="list-group">
                <?php foreach ($categories as $category): ?>
                    <li class="list-group-item">
                        <a href="category.php?id=<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
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
                    $minPrice = filter_var($_GET['min_price'], FILTER_VALIDATE_INT);
                    $maxPrice = filter_var($_GET['max_price'], FILTER_VALIDATE_INT);
                    
                    if ($minPrice !== false && $maxPrice !== false) {
                        $where .= " AND price BETWEEN :min_price AND :max_price";
                        $params[':min_price'] = $minPrice;
                        $params[':max_price'] = $maxPrice;
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
                                <img src="images/placeholder.jpg" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>" style="height: 200px; object-fit: cover;">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">
                                    <a href="product.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted mb-2"><?= number_format($product['price'], 0, '.', ' ') ?> Ft</p>
                                <button onclick="addToCart(<?= $product['id'] ?>, 1)" class="btn btn-primary mt-auto">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('priceRangeChanged', function(e) {
    const minPrice = e.detail.minPrice;
    const maxPrice = e.detail.maxPrice;
    
    // Reload the page with new price range
    window.location.href = `?min_price=${minPrice}&max_price=${maxPrice}`;
});

function addToCart(productId, quantity) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch('update_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cart-count').textContent = '(' + data.cartCount + ')';
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

<?php include 'includes/footer.php'; ?>