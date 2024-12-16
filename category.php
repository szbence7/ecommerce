<?php
include 'includes/header.php';
require_once 'includes/functions.php';

// Kategória lekérése
$stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
$stmt->execute([$_GET['id']]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php');
    exit();
}

// Termékek lekérése a kategóriához
$stmt = $pdo->prepare('SELECT * FROM products WHERE category_id = ?');
$stmt->execute([$_GET['id']]);
$products = $stmt->fetchAll();

?>
<h2><?php echo htmlspecialchars($category['name']); ?></h2>

<div class="row">
    <?php foreach ($products as $product): ?>
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
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

<?php include 'includes/footer.php'; ?>