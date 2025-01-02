<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize checkout session if it doesn't exist
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

if (!defined('INCLUDED_FILES')) {
    define('INCLUDED_FILES', true);
    require 'vendor/autoload.php';
    require_once 'includes/db.php';
    require_once 'includes/functions.php';
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Handle successful Stripe payment
if (isset($_GET['payment']) && $_GET['payment'] === 'success' && isset($_GET['order_id'])) {
    try {
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        
        // Get the payment intent from the URL
        $payment_intent = null;
        if (isset($_GET['payment_intent'])) {
            $payment_intent = \Stripe\PaymentIntent::retrieve($_GET['payment_intent']);
        }
        
        if ($payment_intent && $payment_intent->status === 'succeeded') {
            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET status = 'processing', payment_status = 'paid' WHERE id = ? AND payment_status = 'pending'");
            $stmt->execute([$_GET['order_id']]);
            
            // Clear cart and checkout data
            unset($_SESSION['cart']);
            unset($_SESSION['checkout']);
            unset($_SESSION['points_to_redeem']);
            
            // Store success message
            $_SESSION['order_success'] = true;
            
            // Get order number
            $stmt = $pdo->prepare("SELECT order_number FROM orders WHERE id = ?");
            $stmt->execute([$_GET['order_id']]);
            $_SESSION['order_number'] = $stmt->fetchColumn();
            
            // Redirect to success page
            header('Location: order-success.php');
            exit();
        }
    } catch (Exception $e) {
        error_log("Error processing payment success: " . $e->getMessage());
    }
}

// Check cart after form processing but before output
// Only check if we're not on the success page
if (!isset($_GET['success']) && empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit();
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

// Add this before include 'includes/header.php';
$stripePublicKey = $_ENV['STRIPE_PUBLIC_KEY'];

// Initialize step if not set
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Process form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next_step'])) {
        // Save form data based on current step
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
        // Handle non-card payment methods
        $payment_method = $_POST['payment_method'] ?? '';
        
        if ($payment_method === 'transfer' || $payment_method === 'cash_on_delivery') {
            try {
                // Save order details using POST
                $postData = [
                    'payment_method' => $payment_method,
                    'shipping_method' => $_SESSION['checkout']['shipping_method'],
                    'points_to_redeem' => $_POST['points_to_redeem'] ?? 0
                ];
                
                $ch = curl_init('save-order.php');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                
                $saveOrderResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200) {
                    throw new Exception('Failed to save order');
                }

                $orderData = json_decode($saveOrderResponse, true);
                if (!$orderData['success']) {
                    throw new Exception($orderData['error'] ?? 'Failed to save order');
                }

                // Store success message and order number
                $_SESSION['order_success'] = true;
                $_SESSION['order_number'] = $orderData['order_number'];

                // Clear cart and checkout data
                unset($_SESSION['cart']);
                unset($_SESSION['checkout']);
                unset($_SESSION['points_to_redeem']);

                // Redirect to success page
                header('Location: order-success.php');
                exit();

            } catch (Exception $e) {
                error_log("Order processing error: " . $e->getMessage());
                header('Location: checkout.php?step=3&error=' . urlencode($e->getMessage()));
                exit();
            }
        }
    }
}

// Check if user is logged in and address is in session
if (isset($_SESSION['user_id']) && isset($_SESSION['user_address'])) {
    // Pre-populate form fields with user's address from session
    $_SESSION['checkout']['email'] = $_SESSION['user_address']['email'];
    $_SESSION['checkout']['firstname'] = $_SESSION['user_address']['first_name'];
    $_SESSION['checkout']['lastname'] = $_SESSION['user_address']['last_name'];
    $_SESSION['checkout']['street_address'] = $_SESSION['user_address']['street_address'];
    $_SESSION['checkout']['city'] = $_SESSION['user_address']['city'];
    $_SESSION['checkout']['country'] = $_SESSION['user_address']['country'];
    $_SESSION['checkout']['postal_code'] = $_SESSION['user_address']['postal_code'];
}

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
                                    <input class="form-check-input" type="radio" name="shipping_method" id="personal" value="personal" <?php echo isset($_SESSION['checkout']['shipping_method']) && $_SESSION['checkout']['shipping_method'] === 'personal' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="personal">Személyes átvétel</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="gls" value="gls" <?php echo isset($_SESSION['checkout']['shipping_method']) && $_SESSION['checkout']['shipping_method'] === 'gls' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="gls">GLS</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="dpd" value="dpd" <?php echo isset($_SESSION['checkout']['shipping_method']) && $_SESSION['checkout']['shipping_method'] === 'dpd' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="dpd">DPD</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="mpl" value="mpl" <?php echo isset($_SESSION['checkout']['shipping_method']) && $_SESSION['checkout']['shipping_method'] === 'mpl' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="mpl">MPL</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="automat" value="automat" <?php echo isset($_SESSION['checkout']['shipping_method']) && $_SESSION['checkout']['shipping_method'] === 'automat' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="automat">Csomagautomata</label>
                                </div>
                            </div>
                            <button type="submit" name="next_step" class="btn btn-primary">Következő lépés</button>
                        </form>

                    <?php elseif ($current_step == 3): ?>
                        <h3>3. Fizetési mód</h3>
                        <form method="POST" id="payment-form">
                            <div class="mb-3">
                                <label class="form-label">Válassza ki a fizetési módot:</label>
                                <div class="form-check">
                                    <input class="form-check-input payment-method-radio" type="radio" name="payment_method" id="card" value="card" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'card') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="card">
                                        Bankkártya
                                    </label>
                                    <div id="card-element-container" style="display: none;" class="mt-3">
                                        <div id="payment-element"></div>
                                        <div id="payment-message" class="hidden"></div>
                                    </div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-method-radio" type="radio" name="payment_method" id="transfer" value="transfer" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'transfer') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="transfer">
                                        Banki átutalás
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-method-radio" type="radio" name="payment_method" id="cash_on_delivery" value="cash_on_delivery" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'cash_on_delivery') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cash_on_delivery">
                                        Utánvét
                                    </label>
                                </div>
                            </div>

                            <?php if(isset($_SESSION['user_id'])): ?>
                                <?php
                                    // Get user's points balance
                                    $stmt = $pdo->prepare("SELECT points_balance FROM users WHERE id = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $available_points = $stmt->fetchColumn() ?: 0;
                                ?>
                                <div class="points-redemption mt-4">
                                    <h4>Pontbeváltás</h4>
                                    <p>Elérhető pontok: <strong><?php echo number_format($available_points); ?></strong></p>
                                    <div class="input-group mb-3">
                                        <input type="number" class="form-control" id="points_to_redeem" 
                                               name="points_to_redeem" min="0" max="<?php echo $available_points; ?>" 
                                               value="0" placeholder="Beváltandó pontok">
                                        <button class="btn btn-outline-primary" type="button" id="apply_points">
                                            Pontok beváltása
                                        </button>
                                    </div>
                                    <small class="text-muted">1 pont = 1 EUR értékű kedvezmény</small>
                                </div>
                            <?php endif; ?>

                            <button type="submit" id="submit-button" name="complete_order" class="btn btn-primary mt-3">Rendelés véglegesítése</button>
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
                                    <span><?php echo formatPrice($product['price']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($current_step >= 2): ?>
                            <hr>
                            <div class="d-flex justify-content-between shipping-cost-row">
                                <span>Szállítási költség:</span>
                                <span class="shipping-cost"><?php echo formatPrice($shipping_cost); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div id="points-discount-row" style="display: none;">
                            <hr>
                            <div class="d-flex justify-content-between points-discount">
                                <span>Beváltott pont:</span>
                                <span class="points-discount-amount">-<?php echo formatPrice(0); ?></span>
                            </div>
                        </div>
                        
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Teljes fizetendő:</strong>
                            <strong class="final-total" data-original="<?php echo $final_total; ?>"><?php echo formatPrice($final_total); ?></strong>
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
                
                // Use PHP's formatPrice function through AJAX
                fetch(`format_price.php?price=${shippingCost}`)
                    .then(response => response.text())
                    .then(formattedShippingCost => {
                        document.querySelector('.shipping-cost').textContent = formattedShippingCost;
                    });
                
                fetch(`format_price.php?price=${newTotal}`)
                    .then(response => response.text())
                    .then(formattedTotal => {
                        document.querySelector('.final-total').textContent = formattedTotal;
                    });
            });
        });
    }

    // Points redemption functionality
    const pointsInput = document.getElementById('points_to_redeem');
    const applyPointsBtn = document.getElementById('apply_points');
    const pointsDiscountRow = document.getElementById('points-discount-row');
    const finalTotalElement = document.querySelector('.final-total');
    
    if (applyPointsBtn && pointsInput) {
        applyPointsBtn.addEventListener('click', function() {
            const points = parseInt(pointsInput.value) || 0;
            const originalTotal = parseFloat(finalTotalElement.getAttribute('data-original'));
            
            if (points > 0) {
                // Show the points discount row
                pointsDiscountRow.style.display = 'block';
                
                // Calculate new total (1 point = 1 EUR)
                const discount = points;
                const newTotal = Math.max(0, originalTotal - discount);
                
                // Update the points discount amount display
                fetch(`format_price.php?price=${discount}`)
                    .then(response => response.text())
                    .then(formattedDiscount => {
                        document.querySelector('.points-discount-amount').textContent = '-' + formattedDiscount;
                    });
                
                // Update the final total
                fetch(`format_price.php?price=${newTotal}`)
                    .then(response => response.text())
                    .then(formattedTotal => {
                        finalTotalElement.textContent = formattedTotal;
                    });

                // Save points to session
                fetch('save_points.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        points_to_redeem: points
                    })
                });
            } else {
                // Hide the points discount row if no points are being used
                pointsDiscountRow.style.display = 'none';
                
                // Reset to original total
                fetch(`format_price.php?price=${originalTotal}`)
                    .then(response => response.text())
                    .then(formattedTotal => {
                        finalTotalElement.textContent = formattedTotal;
                    });

                // Clear points from session
                fetch('save_points.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        points_to_redeem: 0
                    })
                });
            }
        });
        
        // Add input validation
        pointsInput.addEventListener('input', function() {
            const maxPoints = parseInt(this.max);
            let value = parseInt(this.value) || 0;
            
            if (value < 0) {
                this.value = 0;
            } else if (value > maxPoints) {
                this.value = maxPoints;
            }
        });
    }
});
</script>

<?php if ($current_step == 3): ?>
<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('<?php echo $stripePublicKey; ?>');
let elements = null;
let paymentElement = null;

document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const paymentMethodRadios = document.querySelectorAll('.payment-method-radio');
    const cardElementContainer = document.getElementById('card-element-container');
    const messageDiv = document.getElementById('payment-message');

    async function initializePaymentElement() {
        try {
            // Create empty Elements instance
            elements = stripe.elements({
                mode: 'payment',
                amount: <?php echo ($final_total * 100); ?>,
                currency: 'eur',
                appearance: {
                    theme: 'stripe'
                }
            });

            // Create and mount the Payment Element
            paymentElement = elements.create('payment');
            await paymentElement.mount('#payment-element');
            messageDiv.classList.add('hidden');
        } catch (error) {
            console.error('Error initializing payment element:', error);
            messageDiv.textContent = error.message;
            messageDiv.style.color = 'red';
            messageDiv.classList.remove('hidden');
        }
    }

    async function handlePaymentMethodChange() {
        const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (selectedPaymentMethod === 'card') {
            cardElementContainer.style.display = 'block';
            messageDiv.classList.remove('hidden');
            messageDiv.textContent = 'Loading payment form...';
            
            try {
                await initializePaymentElement();
                messageDiv.classList.add('hidden');
            } catch (error) {
                console.error('Error:', error);
                messageDiv.textContent = error.message;
                messageDiv.style.color = 'red';
            }
        } else {
            cardElementContainer.style.display = 'none';
            messageDiv.classList.add('hidden');
            if (elements) {
                elements = null;
                paymentElement = null;
                const paymentElementContainer = document.getElementById('payment-element');
                paymentElementContainer.innerHTML = '';
            }
        }
    }

    // Initialize payment method display
    if (document.querySelector('input[name="payment_method"]:checked')) {
        handlePaymentMethodChange();
    }

    // Add change event listeners to radio buttons
    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', handlePaymentMethodChange);
    });

    paymentForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

        if (selectedPaymentMethod === 'card') {
            submitButton.disabled = true;
            messageDiv.textContent = 'Processing payment...';
            messageDiv.classList.remove('hidden');
            messageDiv.style.color = 'rgb(105, 115, 134)';
            
            try {
                // Submit the form data to Stripe first
                const { error: submitError } = await elements.submit();
                if (submitError) {
                    throw submitError;
                }

                // First save the order details
                const formData = new FormData(paymentForm);
                formData.append('payment_method', 'card');
                formData.append('complete_order', 'true');
                formData.append('shipping_method', document.querySelector('input[name="shipping_method"]:checked')?.value || 'personal');
                
                const saveOrderResponse = await fetch('save-order.php', {
                    method: 'POST',
                    body: formData
                });

                if (!saveOrderResponse.ok) {
                    const errorData = await saveOrderResponse.json();
                    throw new Error(errorData.error || 'Failed to save order');
                }

                const orderData = await saveOrderResponse.json();
                
                // Create PaymentIntent
                const createResponse = await fetch('process-stripe-payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        create_payment: true,
                        order_id: orderData.order_id,
                        points_to_redeem: document.getElementById('points_to_redeem')?.value || 0
                    })
                });

                if (!createResponse.ok) {
                    throw new Error('Failed to create payment');
                }

                const { clientSecret } = await createResponse.json();

                // Confirm the payment
                const { error } = await stripe.confirmPayment({
                    clientSecret: clientSecret,
                    elements,
                    confirmParams: {
                        return_url: window.location.origin + '/checkout.php?payment=success&order_id=' + orderData.order_id
                    }
                });

                if (error) {
                    messageDiv.textContent = error.message;
                    messageDiv.style.color = 'red';
                    submitButton.disabled = false;
                    throw error;
                }

            } catch (error) {
                console.error('Payment Error:', error);
                messageDiv.textContent = error.message;
                messageDiv.style.color = 'red';
                messageDiv.classList.remove('hidden');
                submitButton.disabled = false;
            }
        } else {
            // For other payment methods, submit the form normally
            event.target.submit();
        }
    });
});
</script>
<style>
.hidden {
    display: none;
}
#payment-message {
    color: rgb(105, 115, 134);
    font-size: 16px;
    line-height: 20px;
    padding-top: 12px;
    text-align: center;
}
#payment-element {
    margin-bottom: 24px;
}
</style>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>