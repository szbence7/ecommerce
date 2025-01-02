<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get order number from URL
$order_number = isset($_GET['id']) ? $_GET['id'] : 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.email, u.name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$order_number, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: profile.php");
    exit();
}

require_once 'includes/header.php';

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order['id']]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize default values for order details
$orderDetails = [
    'subtotal' => 0.00,
    'shipping_cost' => 0.00,
    'tax' => 0.00,
    'total' => 0.00,
    'shipping_address' => '',
    'billing_address' => ''
];

// Calculate subtotal from order items
$subtotal = 0;
foreach ($orderItems as $item) {
    $subtotal += $item['quantity'] * $item['price'];
}

// Calculate shipping cost based on shipping method
$shipping_cost = ($order['shipping_method'] === 'personal') ? 0 : 5.99;

// Merge with actual order details if they exist
if (isset($order)) {
    $orderDetails = array_merge($orderDetails, [
        'subtotal' => $subtotal,
        'shipping_cost' => $shipping_cost,
        'tax' => 0.00,
        'total' => $subtotal + $shipping_cost,
        'shipping_address' => sprintf(
            "%s %s\n%s\n%s, %s\n%s",
            $order['firstname'] ?? '',
            $order['lastname'] ?? '',
            $order['street_address'] ?? '',
            $order['city'] ?? '',
            $order['postal_code'] ?? '',
            $order['country'] ?? ''
        ),
        'billing_address' => sprintf(
            "%s %s\n%s\n%s, %s\n%s",
            $order['firstname'] ?? '',
            $order['lastname'] ?? '',
            $order['street_address'] ?? '',
            $order['city'] ?? '',
            $order['postal_code'] ?? '',
            $order['country'] ?? ''
        )
    ]);
}
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
    
    .timeline {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        width: 100%;
    }
    
    .timeline-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        flex: 1;
    }
    
    .timeline-step:not(:last-child):before {
        content: '';
        position: absolute;
        left: 50%;
        top: 1rem;
        width: 100%;
        height: 2px;
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
        margin-bottom: 0.5rem;
    }
    
    .timeline-step.active .step-icon {
        border-color: #0d6efd;
        background: #0d6efd;
        color: white;
    }
    
    .timeline-step .step-content {
        text-align: center;
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
                    <h1 class="h3 mb-2">Order #<?= htmlspecialchars($order['order_number']) ?></h1>
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
                    $steps = ['Ordered', 'Payment', 'Processing', 'Shipped', 'Delivered'];
                    
                    // Check if payment is completed or if it's cash on delivery
                    $paymentCompleted = $order['payment_status'] === 'paid';
                    $isCOD = $order['payment_method'] === 'cash_on_delivery';
                    $isCardPaid = $order['payment_method'] !== 'cash_on_delivery' && $paymentCompleted;
                    
                    // Set current step based on order status and payment completion
                    $currentStep = 0;
                    
                    if ($order['status'] === 'shipped') {
                        $currentStep = array_search('Shipped', $steps);
                    } elseif ($order['status'] === 'delivered') {
                        $currentStep = array_search('Delivered', $steps);
                    } elseif ($order['status'] === 'processing') {
                        $currentStep = array_search('Processing', $steps);
                    } elseif ($paymentCompleted || $isCOD) {
                        $currentStep = array_search('Payment', $steps);
                    }
                    
                    foreach ($steps as $index => $step):
                        $isActive = $index <= $currentStep;
                    ?>
                    <div class="timeline-step <?= $isActive ? 'active' : '' ?>">
                        <div class="step-icon">
                            <i class="bi bi-check"></i>
                        </div>
                        <div class="step-content">
                            <h6 class="mb-1"><?= $step ?></h6>
                            <?php if ($isActive): ?>
                                <?php if ($step === 'Payment' && $isCOD): ?>
                                    <small class="text-muted">Cash on Delivery</small>
                                <?php else: ?>
                                    <small class="text-muted"><?= date('M j, Y') ?></small>
                                <?php endif; ?>
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
                            <?= formatPrice($item['price']) ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <h6 class="mb-0"><?= formatPrice($item['quantity'] * $item['price']) ?></h6>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-md-6 offset-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span><?= formatPrice($orderDetails['subtotal']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span><?= formatPrice($orderDetails['shipping_cost']) ?></span>
                        </div>
                        <?php if ($orderDetails['tax'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax</span>
                            <span><?= formatPrice($orderDetails['tax']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span><?= formatPrice($orderDetails['total']) ?></span>
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
                    <?= nl2br(htmlspecialchars($orderDetails['shipping_address'])) ?>
                </address>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="address-card">
                <h5 class="mb-3">Billing Address</h5>
                <address class="mb-0">
                    <?= nl2br(htmlspecialchars($orderDetails['billing_address'])) ?>
                </address>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
