<?php
include 'includes/header.php';
require_once 'includes/functions.php';
require_once 'includes/components/sale_badge.php';

// Kategória lekérése
$stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
$stmt->execute([$_GET['id']]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php');
    exit();
}

// Termékek lekérése a kategóriához
$stmt = $pdo->prepare('SELECT p.*, pt.short_description, pt.name as translated_name 
                       FROM products p 
                       LEFT JOIN product_translations pt ON p.id = pt.product_id 
                       AND pt.language_code = :lang_code 
                       WHERE p.category_id = :category_id');
$stmt->execute([
    ':category_id' => $_GET['id'],
    ':lang_code' => getCurrentLanguage()
]);
$products = $stmt->fetchAll();

?>
<h2><?php 
    $categoryName = getEntityTranslation('category', $category['id'], 'name', $category['name']);
    echo htmlspecialchars($categoryName); 
?></h2>

<div class="row">
    <?php foreach ($products as $product): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="product-image-container">
                    <?php if ($product['is_on_sale']): ?>
                        <?php renderSaleBadge(); ?>
                    <?php endif; ?>
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($product['translated_name'] ?? $product['name']); ?>"
                         style="height: 200px; object-fit: cover;">
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['translated_name'] ?? $product['name']); ?></h5>
                    <p class="card-text flex-grow-1"><?php echo htmlspecialchars($product['short_description']); ?></p>
                    <div class="mt-auto">
                        <?php if ($product['is_on_sale'] && $product['discount_price']): ?>
                            <p class="card-text mb-2">
                                <span class="text-muted text-decoration-line-through"><?= formatPrice($product['price']) ?></span>
                                <span class="h5 text-danger ms-2"><?= formatPrice($product['discount_price']) ?></span>
                            </p>
                        <?php else: ?>
                            <p class="card-text mb-2"><?= formatPrice($product['price']) ?></p>
                        <?php endif; ?>
                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-primary">
                            <?= __t('cart.add', 'shop') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>