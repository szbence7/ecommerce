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

// Clear success flags after displaying
unset($_SESSION['order_number']);
unset($_SESSION['order_success']);

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title mb-4">Köszönjük a rendelését!</h2>
                    <p class="lead">A rendelési száma: <strong><?php echo htmlspecialchars($orderNumber); ?></strong></p>
                    <p class="mt-4">A rendelés részleteiről e-mailben tájékoztatjuk.</p>
                    <a href="index.php" class="btn btn-primary mt-3">Vissza a főoldalra</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
