<?php
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/db.php';

function getShopCurrency() {
    // First check session
    if (isset($_SESSION['currency'])) {
        return $_SESSION['currency'];
    }
    
    // If not in session, get from database
    global $pdo;
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'currency'");
    $dbCurrency = $stmt->fetchColumn();
    
    // Store in session and return
    if ($dbCurrency) {
        $_SESSION['currency'] = $dbCurrency;
        return $dbCurrency;
    }
    
    return 'EUR'; // Default to EUR if not set
}

function getExchangeRate($currency) {
    // First check session
    if (isset($_SESSION['exchange_rates'][$currency])) {
        return $_SESSION['exchange_rates'][$currency];
    }
    
    // If not in session, get from database
    global $pdo;
    $stmt = $pdo->prepare("SELECT rate FROM exchange_rates WHERE currency = ?");
    $stmt->execute([$currency]);
    $rate = $stmt->fetchColumn();
    
    // Store in session and return
    if ($rate) {
        if (!isset($_SESSION['exchange_rates'])) {
            $_SESSION['exchange_rates'] = [];
        }
        $_SESSION['exchange_rates'][$currency] = $rate;
        return $rate;
    }
    
    return 1; // Default to 1 if not found
}

function formatPrice($price, $forceCurrency = null) {
    $currency = $forceCurrency ?? getShopCurrency();
    $rate = getExchangeRate($currency);
    
    // Convert price to target currency
    $convertedPrice = $price * $rate;
    
    // Format based on currency
    switch ($currency) {
        case 'HUF':
            return number_format($convertedPrice, 0, ',', ' ') . ' Ft';
        case 'EUR':
            return number_format($convertedPrice, 2, ',', ' ') . ' €';
        case 'USD':
            return '$' . number_format($convertedPrice, 2, '.', ',');
        case 'GBP':
            return '£' . number_format($convertedPrice, 2, '.', ',');
        default:
            return number_format($convertedPrice, 2, '.', ',') . ' ' . $currency;
    }
}

function convertToEUR($price, $fromCurrency) {
    if ($fromCurrency === 'EUR') return $price;
    $rate = getExchangeRate($fromCurrency);
    return $price / $rate;
}

function updateUserSession() {
    global $pdo;
    
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'] ?? 'customer' // Set default role if not set
        ];
    }
}

function getSetting($pdo, $key, $default = null) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value !== false ? $value : $default;
}

function checkExpiredDiscounts($pdo) {
    $stmt = $pdo->prepare("
        UPDATE products 
        SET discount_price = NULL, 
            is_on_sale = 0,
            discount_end_time = NULL 
        WHERE discount_end_time IS NOT NULL 
        AND discount_end_time < NOW()
    ");
    $stmt->execute();
}

function get_product($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Check expired discounts on every page load
checkExpiredDiscounts($pdo);