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

<div class="row">
    <!-- Categories -->
    <div class="col-md-3">
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

    <!-- Products -->
    <div class="col-md-9">
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <a href="product.php?id=<?= $product['id'] ?>">
                            <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                        <div class="card-body">
                            <a href="product.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            </a>
                            <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                            <p class="card-text"><?php echo formatPrice($product['price']); ?></p>
                            <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-primary">Add to Cart</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>