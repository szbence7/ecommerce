<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';

if (empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit;
}

// Initialize step if not set
if (!isset($_GET['step'])) {
    $_GET['step'] = 1;
}

// Handle success page
if (isset($_GET['success']) && $_GET['success'] === 'true') {
    $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h2 class="mb-4">Köszönjük a rendelést!</h2>
                <p class="lead">Rendelési szám: <strong><?php echo $orderNumber; ?></strong></p>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

$total = 0;
$products = [];
foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $product = get_product($product_id);
    if ($product) {
        $products[] = $product;
        $total += $product['price'] * $quantity;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next_step'])) {
        $nextStep = $_GET['step'] + 1;
        header("Location: checkout.php?step=$nextStep");
        exit;
    } elseif (isset($_POST['complete_order'])) {
        header('Location: checkout.php?success=true');
        exit;
    }
}

// Shipping costs
$shipping_costs = [
    'personal' => 0,
    'gls' => 5.99,
    'dpd' => 5.99,
    'mpl' => 5.99,
    'automat' => 5.99
];

$selected_shipping = isset($_POST['shipping_method']) ? $_POST['shipping_method'] : 'personal';
$shipping_cost = $shipping_costs[$selected_shipping] ?? 0;
$final_total = $total + $shipping_cost;
?>

<div class="container mt-4">
    <div class="row">
        <!-- Left Column - Checkout Steps -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <?php if ($_GET['step'] == 1): ?>
                        <h3>1. Személyes adatok</h3>
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email cím</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstname" class="form-label">Keresztnév</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastname" class="form-label">Vezetéknév</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="billing_address" class="form-label">Számlázási cím</label>
                                <textarea class="form-control" id="billing_address" name="billing_address" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="next_step" class="btn btn-primary">Következő lépés</button>
                        </form>

                    <?php elseif ($_GET['step'] == 2): ?>
                        <h3>2. Szállítási mód</h3>
                        <form method="POST" class="shipping-form">
                            <div class="mb-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="personal" value="personal" checked>
                                    <label class="form-check-label" for="personal">Személyes átvétel</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="gls" value="gls">
                                    <label class="form-check-label" for="gls">GLS</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="dpd" value="dpd">
                                    <label class="form-check-label" for="dpd">DPD</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="mpl" value="mpl">
                                    <label class="form-check-label" for="mpl">MPL</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="automat" value="automat">
                                    <label class="form-check-label" for="automat">Csomagautomata</label>
                                </div>
                            </div>
                            <button type="submit" name="next_step" class="btn btn-primary">Következő lépés</button>
                        </form>

                    <?php elseif ($_GET['step'] == 3): ?>
                        <h3>3. Fizetési mód</h3>
                        <form method="POST">
                            <div class="mb-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
                                    <label class="form-check-label" for="cod">Utánvétes fizetés</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="transfer" value="transfer">
                                    <label class="form-check-label" for="transfer">Átutalás</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="card" value="card">
                                    <label class="form-check-label" for="card">Kártyás fizetés</label>
                                </div>
                            </div>
                            <button type="submit" name="complete_order" class="btn btn-success">Fizetés most</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column - Order Summary -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Rendelés összesítő</h4>
                    <div class="order-summary">
                        <?php foreach ($products as $product): ?>
                            <div class="product-item mb-2">
                                <div class="d-flex justify-content-between">
                                    <span><?php echo $product['name']; ?></span>
                                    <span><?php echo number_format($product['price'], 2); ?> €</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($_GET['step'] >= 2): ?>
                            <hr>
                            <div class="d-flex justify-content-between shipping-cost-row">
                                <span>Szállítási költség:</span>
                                <span class="shipping-cost"><?php echo number_format($shipping_cost, 2); ?> €</span>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Teljes fizetendő:</strong>
                            <strong class="final-total"><?php echo number_format($final_total, 2); ?> €</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const shippingInputs = document.querySelectorAll('input[name="shipping_method"]');
    if (shippingInputs.length > 0) {
        shippingInputs.forEach(input => {
            input.addEventListener('change', function() {
                const shippingCosts = {
                    'personal': 0,
                    'gls': 5.99,
                    'dpd': 5.99,
                    'mpl': 5.99,
                    'automat': 5.99
                };
                
                const selectedMethod = this.value;
                const shippingCost = shippingCosts[selectedMethod];
                const subtotal = <?php echo $total; ?>;
                const newTotal = subtotal + shippingCost;
                
                document.querySelector('.shipping-cost').textContent = shippingCost.toFixed(2) + ' €';
                document.querySelector('.final-total').textContent = newTotal.toFixed(2) + ' €';
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>