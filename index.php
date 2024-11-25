<?php
include 'includes/header.php';
require_once 'includes\functions.php';

// Kategóriák lekérése
$stmt = $pdo->query('SELECT * FROM categories');
$categories = $stmt->fetchAll();

// Termékek lekérése
$stmt = $pdo->query('SELECT * FROM products');
$products = $stmt->fetchAll();
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
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
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