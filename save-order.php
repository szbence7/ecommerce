<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required session data
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        throw new Exception('Cart is empty');
    }

    if (!isset($_SESSION['checkout']) || empty($_SESSION['checkout'])) {
        throw new Exception('Checkout data is missing');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User must be logged in to place an order');
    }

    // Validate required POST data
    if (!isset($_POST['shipping_method'])) {
        throw new Exception('Shipping method is required');
    }

    if (!isset($_POST['payment_method'])) {
        throw new Exception('Payment method is required');
    }

    // Determine payment status based on payment method
    $payment_status = 'pending_payment'; // default
    $order_status = 'pending';
    
    switch ($_POST['payment_method']) {
        case 'card':
            $payment_status = 'pending';
            $order_status = 'pending';
            break;
        case 'transfer':
            $payment_status = 'pending_payment';
            $order_status = 'pending';
            break;
        case 'cash_on_delivery':
            $payment_status = 'cash_on_delivery';
            $order_status = 'processing';
            break;
        default:
            throw new Exception('Invalid payment method');
    }

    // Calculate total
    $total = 0;
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        if (!$product) {
            throw new Exception('Product not found: ' . $product_id);
        }
        $total += $product['price'] * $quantity;
    }

    // Add shipping cost
    $shipping_costs = [
        'personal' => 0,
        'gls' => 5.99,
        'dpd' => 5.99,
        'mpl' => 5.99,
        'automat' => 5.99
    ];
    
    $selected_shipping = $_POST['shipping_method'];
    if (!isset($shipping_costs[$selected_shipping])) {
        throw new Exception('Invalid shipping method selected');
    }
    
    $shipping_cost = $shipping_costs[$selected_shipping];
    $final_total = $total + $shipping_cost;

    // Handle points redemption
    $points_redeemed = isset($_POST['points_to_redeem']) ? intval($_POST['points_to_redeem']) : 0;
    if ($points_redeemed > 0) {
        $stmt = $pdo->prepare("SELECT points_balance FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $available_points = $stmt->fetchColumn();
        
        if ($points_redeemed > $available_points) {
            throw new Exception('Not enough points available');
        }
        
        // Deduct points from user's balance
        $stmt = $pdo->prepare("UPDATE users SET points_balance = points_balance - ? WHERE id = ?");
        $stmt->execute([$points_redeemed, $_SESSION['user_id']]);
        
        // Adjust total amount
        $final_total = max(0, $final_total - $points_redeemed);
    }

    // Generate order number
    $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                order_number, user_id, status, total_amount, payment_method, 
                payment_status, shipping_method, email, firstname, lastname,
                street_address, city, country, postal_code, points_used
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $orderNumber,
            $_SESSION['user_id'],
            $order_status,
            $final_total,
            $_POST['payment_method'],
            $payment_status,
            $selected_shipping,
            $_SESSION['checkout']['email'],
            $_SESSION['checkout']['firstname'],
            $_SESSION['checkout']['lastname'],
            $_SESSION['checkout']['street_address'],
            $_SESSION['checkout']['city'],
            $_SESSION['checkout']['country'],
            $_SESSION['checkout']['postal_code'],
            $points_redeemed
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
            
            if (!$product) {
                throw new Exception('Product not found during order items creation');
            }
            
            $stmt->execute([
                $orderId,
                $product_id,
                $quantity,
                $product['price']
            ]);
        }
        
        // Calculate and award points for the purchase
        if ($_POST['payment_method'] !== 'card') { // Only award points for non-card payments immediately
            $points = updateUserPoints($_SESSION['user_id'], $total, $pdo);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Return success response with order ID
        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception('Database error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log('Save order error: ' . $e->getMessage());
    
    // Rollback transaction on error if it's still active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 