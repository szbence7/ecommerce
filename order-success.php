<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if no successful order
if (!isset($_SESSION['order_success']) || !isset($_SESSION['order_number'])) {
    header('Location: index.php');
    exit();
}

$orderNumber = $_SESSION['order_number'];
$pointsEarned = $_SESSION['points_earned'] ?? 0;

// Clear success flags after displaying
unset($_SESSION['order_number']);
unset($_SESSION['order_success']);
unset($_SESSION['points_earned']);

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title mb-4">Köszönjük a rendelését!</h2>
                    <p class="lead">A rendelési száma: <strong><?php echo htmlspecialchars($orderNumber); ?></strong></p>
                    <?php if ($pointsEarned > 0): ?>
                        <div class="alert alert-success mt-3">
                            <i class="bi bi-star-fill me-2"></i>
                            Gratulálunk! <?= str_replace('{points}', $pointsEarned, __t('user.points')) ?> jóváírva!
                        </div>
                    <?php endif; ?>
                    <p class="mt-4">A rendelés részleteiről e-mailben tájékoztatjuk.</p>
                    <a href="order-details.php?id=<?php echo htmlspecialchars($orderNumber); ?>" class="btn btn-secondary mt-3 me-2">Részletek</a>
                    <a href="index.php" class="btn btn-primary mt-3">Vissza a főoldalra</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
