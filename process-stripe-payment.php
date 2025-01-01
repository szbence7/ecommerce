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
        $total += floatval($product['price']) * intval($quantity);
    }

    // Add shipping cost
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
    
    // Convert to smallest currency unit (pence for GBP)
    $stripe_amount = (int)round($final_total * 100);
    
    error_log("Final total: " . $final_total);
    error_log("Stripe amount: " . $stripe_amount);

    // Create PaymentIntent
    $payment_intent = \Stripe\PaymentIntent::create([
        'amount' => $stripe_amount,
        'currency' => 'gbp',
        'automatic_payment_methods' => [
            'enabled' => true,
        ]
    ]);

    $output = [
        'clientSecret' => $payment_intent->client_secret
    ];

    echo json_encode($output);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 