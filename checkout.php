<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('INCLUDED_FILES')) {
    define('INCLUDED_FILES', true);
    require_once 'includes/db.php';
    require_once 'includes/functions.php';
}

// Initialize step if not set
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Process form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next_step'])) {
        // Save form data based on current step
        if (!isset($_SESSION['checkout'])) {
            $_SESSION['checkout'] = [
                'email' => '',
                'firstname' => '',
                'lastname' => '',
                'street_address' => '',
                'city' => '',
                'country' => 'HU',
                'postal_code' => '',
                'shipping_method' => 'personal',
                'payment_method' => 'transfer'
            ];
        }
        
        if ($current_step == 1) {
            $_SESSION['checkout']['email'] = $_POST['email'];
            $_SESSION['checkout']['firstname'] = $_POST['firstname'];
            $_SESSION['checkout']['lastname'] = $_POST['lastname'];
            $_SESSION['checkout']['street_address'] = $_POST['street_address'];
            $_SESSION['checkout']['city'] = $_POST['city'];
            $_SESSION['checkout']['country'] = $_POST['country'];
            $_SESSION['checkout']['postal_code'] = $_POST['postal_code'];
            
            header("Location: checkout.php?step=2");
            exit();
        } elseif ($current_step == 2) {
            $_SESSION['checkout']['shipping_method'] = $_POST['shipping_method'];
            
            header("Location: checkout.php?step=3");
            exit();
        }
    } elseif (isset($_POST['complete_order'])) {
        $_SESSION['checkout']['payment_method'] = $_POST['payment_method'];
        
        // Generate order number
        $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Determine payment status based on payment method
        $paymentStatus = 'pending_payment'; // default
        switch ($_POST['payment_method']) {
            case 'card':
                $paymentStatus = 'paid';
                break;
            case 'transfer':
                $paymentStatus = 'pending_payment';
                break;
            case 'cash_on_delivery':
                $paymentStatus = 'cash_on_delivery';
                break;
        }
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Calculate total
            $total = 0;
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                $total += $product['price'] * $quantity;
            }
            
            // Insert order
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    order_number, user_id, status, total_amount, payment_method, 
                    payment_status, shipping_method, email, firstname, lastname,
                    street_address, city, country, postal_code
                ) VALUES (
                    ?, ?, 'processing', ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?
                )
            ");
            
            $userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0;
            $stmt->execute([
                $orderNumber,
                $userId,
                $total,
                $_POST['payment_method'],
                $paymentStatus,
                $_SESSION['checkout']['shipping_method'],
                $_SESSION['checkout']['email'],
                $_SESSION['checkout']['firstname'],
                $_SESSION['checkout']['lastname'],
                $_SESSION['checkout']['street_address'],
                $_SESSION['checkout']['city'],
                $_SESSION['checkout']['country'],
                $_SESSION['checkout']['postal_code']
            ]);
            
            $orderId = $pdo->lastInsertId();
            
            // Insert order items
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                $priceStmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                $priceStmt->execute([$product_id]);
                $product = $priceStmt->fetch();
                
                $stmt->execute([
                    $orderId,
                    $product_id,
                    $quantity,
                    $product['price']
                ]);
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Store order number in session for thank you page
            $_SESSION['order_number'] = $orderNumber;
            
            // Clear the cart
            $_SESSION['cart'] = array();
            
            header('Location: checkout.php?success=true');
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            error_log("Order creation failed: " . $e->getMessage());
            header('Location: checkout.php?error=1');
            exit();
        }
    }
}

// Check cart after form processing but before output
// Only check if we're not on the success page
if (!isset($_GET['success']) && empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit();
}

// Initialize checkout session if not exists
if (!isset($_SESSION['checkout'])) {
    $_SESSION['checkout'] = [
        'email' => '',
        'firstname' => '',
        'lastname' => '',
        'street_address' => '',
        'city' => '',
        'country' => 'HU',
        'postal_code' => '',
        'shipping_method' => 'personal',
        'payment_method' => 'transfer'
    ];
}

// Countries list
$countries = [
    'HU' => 'Magyarország',
    'AT' => 'Ausztria',
    'SK' => 'Szlovákia',
    'RO' => 'Románia',
    'HR' => 'Horvátország',
    'SI' => 'Szlovénia',
    'RS' => 'Szerbia',
    'UA' => 'Ukrajna',
    'DE' => 'Németország',
    'PL' => 'Lengyelország',
    'CZ' => 'Csehország'
];

// Now we can include files that might output content
include 'includes/header.php';

// Success page (after header)
if (isset($_GET['success']) && $_GET['success'] === 'true') {
    $orderNumber = $_SESSION['order_number'] ?? 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
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
    <?php
    include 'includes/footer.php';
    exit();
}

// Calculate totals
$total = 0;
$products = [];
foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $product = get_product($product_id);
    if ($product) {
        $products[] = $product;
        $total += $product['price'] * $quantity;
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

$selected_shipping = isset($_POST['shipping_method']) ? $_POST['shipping_method'] : $_SESSION['checkout']['shipping_method'];
$shipping_cost = $shipping_costs[$selected_shipping] ?? 0;
$final_total = $total + $shipping_cost;

?>

<div class="container mt-4">
    <div class="row">
        <!-- Left Column - Checkout Steps -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <?php if ($current_step == 1): ?>
                        <h3>1. Személyes adatok</h3>
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email cím</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['checkout']['email']); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstname" class="form-label">Keresztnév</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($_SESSION['checkout']['firstname']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastname" class="form-label">Vezetéknév</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($_SESSION['checkout']['lastname']); ?>" required>
                                </div>
                            </div>
                            <h4 class="mb-3">Számlázási cím</h4>
                            <div class="mb-3">
                                <label for="street_address" class="form-label">Utca, házszám</label>
                                <input type="text" class="form-control" id="street_address" name="street_address" value="<?php echo htmlspecialchars($_SESSION['checkout']['street_address']); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">Város</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($_SESSION['checkout']['city']); ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="country" class="form-label">Ország</label>
                                    <select class="form-select" id="country" name="country" required>
                                        <?php foreach ($countries as $code => $name): ?>
                                            <option value="<?php echo $code; ?>" <?php echo $_SESSION['checkout']['country'] === $code ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="postal_code" class="form-label">Irányítószám</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($_SESSION['checkout']['postal_code']); ?>" required>
                                </div>
                            </div>
                            <button type="submit" name="next_step" class="btn btn-primary">Következő lépés</button>
                        </form>

                    <?php elseif ($current_step == 2): ?>
                        <h3>2. Szállítási mód</h3>
                        <form method="POST" class="shipping-form">
                            <div class="mb-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="personal" value="personal" <?php echo $_SESSION['checkout']['shipping_method'] === 'personal' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="personal">Személyes átvétel</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="gls" value="gls" <?php echo $_SESSION['checkout']['shipping_method'] === 'gls' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="gls">GLS</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="dpd" value="dpd" <?php echo $_SESSION['checkout']['shipping_method'] === 'dpd' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="dpd">DPD</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="mpl" value="mpl" <?php echo $_SESSION['checkout']['shipping_method'] === 'mpl' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="mpl">MPL</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="automat" value="automat" <?php echo $_SESSION['checkout']['shipping_method'] === 'automat' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="automat">Csomagautomata</label>
                                </div>
                            </div>
                            <button type="submit" name="next_step" class="btn btn-primary">Következő lépés</button>
                        </form>

                    <?php elseif ($current_step == 3): ?>
                        <h3>3. Fizetési mód</h3>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Válassza ki a fizetési módot:</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="card" value="card" <?php echo ($_SESSION['checkout']['payment_method'] == 'card') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="card">
                                        Bankkártya
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="transfer" value="transfer" <?php echo ($_SESSION['checkout']['payment_method'] == 'transfer') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="transfer">
                                        Banki átutalás
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cash_on_delivery" value="cash_on_delivery" <?php echo ($_SESSION['checkout']['payment_method'] == 'cash_on_delivery') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cash_on_delivery">
                                        Utánvét
                                    </label>
                                </div>
                            </div>
                            <button type="submit" name="complete_order" class="btn btn-primary">Rendelés véglegesítése</button>
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
                        
                        <?php if ($current_step >= 2): ?>
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