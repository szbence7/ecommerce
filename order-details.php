<?php
require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.email, u.name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: profile.php");
    exit();
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .order-status {
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-weight: 500;
        font-size: 0.875rem;
    }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-processing { background: #cce5ff; color: #004085; }
    .status-shipped { background: #d4edda; color: #155724; }
    .status-delivered { background: #d1e7dd; color: #0f5132; }
    
    .timeline-step {
        display: flex;
        position: relative;
        min-height: 5rem;
    }
    
    .timeline-step:not(:last-child):before {
        content: '';
        position: absolute;
        left: 1rem;
        top: 2rem;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }
    
    .timeline-step .step-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
    }
    
    .timeline-step.active .step-icon {
        border-color: #0d6efd;
        background: #0d6efd;
        color: white;
    }
    
    .timeline-step .step-content {
        margin-left: 1rem;
    }
    
    .order-card {
        border: 1px solid rgba(0,0,0,.1);
        border-radius: 1rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
        transition: all 0.3s ease;
    }
    
    .order-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
    }
    
    .product-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 0.5rem;
    }
    
    .address-card {
        background: #f8f9fa;
        border-radius: 1rem;
        padding: 1.5rem;
    }
</style>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">Order #<?= htmlspecialchars($order['id']) ?></h1>
                    <p class="text-muted mb-0">Placed on <?= date('F j, Y', strtotime($order['created_at'])) ?></p>
                </div>
                <span class="order-status status-<?= strtolower($order['status']) ?>">
                    <?= ucfirst(htmlspecialchars($order['status'])) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Order Timeline -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="order-card p-4">
                <h5 class="mb-4">Order Progress</h5>
                <div class="timeline">
                    <?php
                    $steps = ['Ordered', 'Processing', 'Shipped', 'Delivered'];
                    $currentStep = array_search(ucfirst($order['status']), $steps);
                    foreach ($steps as $index => $step):
                    ?>
                    <div class="timeline-step <?= $index <= $currentStep ? 'active' : '' ?>">
                        <div class="step-icon">
                            <i class="bi bi-check"></i>
                        </div>
                        <div class="step-content">
                            <h6 class="mb-1"><?= $step ?></h6>
                            <?php if ($index <= $currentStep): ?>
                            <small class="text-muted"><?= date('M j, Y') ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="order-card p-4">
                <h5 class="mb-4">Order Items</h5>
                <?php foreach ($orderItems as $item): ?>
                <div class="d-flex align-items-center mb-3">
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="product-image">
                    <div class="ms-3 flex-grow-1">
                        <h6 class="mb-1"><?= htmlspecialchars($item['product_name']) ?></h6>
                        <p class="mb-0 text-muted">
                            Quantity: <?= htmlspecialchars($item['quantity']) ?> × 
                            $<?= number_format($item['price'], 2) ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <h6 class="mb-0">$<?= number_format($item['quantity'] * $item['price'], 2) ?></h6>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-md-6 offset-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>$<?= number_format($order['subtotal'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span>$<?= number_format($order['shipping_cost'], 2) ?></span>
                        </div>
                        <?php if ($order['tax'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax</span>
                            <span>$<?= number_format($order['tax'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span>$<?= number_format($order['total'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipping and Billing -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="address-card">
                <h5 class="mb-3">Shipping Address</h5>
                <address class="mb-0">
                    <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                </address>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="address-card">
                <h5 class="mb-3">Billing Address</h5>
                <address class="mb-0">
                    <?= nl2br(htmlspecialchars($order['billing_address'])) ?>
                </address>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
