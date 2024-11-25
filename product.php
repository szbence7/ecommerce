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
?>

<div class="row">
    <div class="col-md-6">
        <?php if($product['image']): ?>
            <img src="<?= htmlspecialchars($product['image']) ?>" class="img-fluid" alt="<?= htmlspecialchars($product['name']) ?>">
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <p class="lead"><?= formatPrice($product['price']) ?></p>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <button onclick="addToCart(<?= $product['id'] ?>)" class="btn btn-lg btn-success">Add to Cart</button>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 