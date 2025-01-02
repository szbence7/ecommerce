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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['create_payment']) || !isset($input['order_id'])) {
        throw new Exception('Invalid request data');
    }

    // Get order details
    $stmt = $pdo->prepare("SELECT total_amount FROM orders WHERE id = ?");
    $stmt->execute([$input['order_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Create PaymentIntent
    $payment_intent = \Stripe\PaymentIntent::create([
        'amount' => round($order['total_amount'] * 100), // Convert to cents
        'currency' => 'eur',
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
        'metadata' => [
            'order_id' => $input['order_id']
        ]
    ]);

    // Update order with payment intent ID
    $stmt = $pdo->prepare("UPDATE orders SET stripe_payment_intent = ? WHERE id = ?");
    $stmt->execute([$payment_intent->id, $input['order_id']]);

    echo json_encode([
        'clientSecret' => $payment_intent->client_secret
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 