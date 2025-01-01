<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

header('Content-Type: application/json');

try {
    // Debug logging
    error_log("Starting payment process");
    error_log("Session cart: " . print_r($_SESSION['cart'], true));
    error_log("Session checkout: " . print_r($_SESSION['checkout'], true));

    // Get current currency
    $currentCurrency = getShopCurrency();
    error_log("Current shop currency: " . $currentCurrency);

    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        throw new Exception('Cart is empty');
    }

    // Calculate total
    $total = 0;
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        if (!$product) {
            throw new Exception("Product not found: " . $product_id);
        }
        // Convert string to float and multiply
        $total += floatval($product['price']) * intval($quantity);
    }

    error_log("Calculated total before shipping: " . $total);

    // Add shipping cost if selected
    $shipping_costs = [
        'personal' => 0,
        'gls' => 5.99,
        'dpd' => 5.99,
        'mpl' => 5.99,
        'automat' => 5.99
    ];
    
    $selected_shipping = $_SESSION['checkout']['shipping_method'] ?? 'personal';
    $shipping_cost = floatval($shipping_costs[$selected_shipping] ?? 0);
    
    // Calculate final total
    $final_total = $total + $shipping_cost;
    error_log("Final total with shipping (before rounding): " . $final_total);
    
    // Set currency and convert amount based on currency
    $stripe_currency = strtolower($currentCurrency);
    $stripe_amount = 0;
    
    switch($stripe_currency) {
        case 'gbp':
            // For GBP, convert to pence (multiply by 100)
            $stripe_amount = (int)round($final_total * 100);
            break;
        case 'huf':
            // For HUF, round to whole numbers (no decimals)
            $stripe_amount = (int)round($final_total);
            break;
        case 'eur':
            // For EUR, convert to cents (multiply by 100)
            $stripe_amount = (int)round($final_total * 100);
            break;
        default:
            throw new Exception("Unsupported currency: " . $currentCurrency);
    }
    
    error_log("Stripe currency: " . $stripe_currency);
    error_log("Stripe amount (in smallest currency unit): " . $stripe_amount);

    // Create PaymentIntent
    try {
        $payment_intent = \Stripe\PaymentIntent::create([
            'amount' => $stripe_amount,
            'currency' => $stripe_currency,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'metadata' => [
                'order_id' => 'PENDING-' . uniqid(),
                'customer_email' => $_SESSION['checkout']['email'] ?? ''
            ]
        ]);

        error_log("Payment intent created successfully: " . $payment_intent->id);
        error_log("Payment intent amount: " . $payment_intent->amount);
        error_log("Payment intent currency: " . $payment_intent->currency);

        $output = [
            'clientSecret' => $payment_intent->client_secret,
        ];

        echo json_encode($output);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe API Error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 