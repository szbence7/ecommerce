<?php
include 'includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Get order details
$sql = "SELECT o.*, u.name as customer 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    header('Location: index.php');
    exit;
}
?>

<div class="text-center my-5">
    <i class="fas fa-check-circle text-success" style="font-size: 64px;"></i>
    <h1 class="mt-4">Thank You for Your Order!</h1>
    <p class="lead">Your order #<?= $order_id ?> has been placed successfully.</p>
    
    <div class="card mt-4 mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h5 class="card-title">Order Details</h5>
            <p class="card-text">
                <strong>Order Number:</strong> #<?= $order_id ?><br>
                <strong>Total Amount:</strong> $<?= number_format($order['total'], 2) ?><br>
                <strong>Status:</strong> <?= ucfirst($order['status']) ?><br>
                <strong>Date:</strong> <?= date('M d, Y H:i', strtotime($order['created_at'])) ?>
            </p>
            
            <p class="mb-0">
                We'll send you an email with order confirmation and tracking details.
            </p>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="index.php" class="btn btn-primary">Continue Shopping</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
