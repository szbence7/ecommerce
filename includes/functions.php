<?php
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
    
    return 'HUF'; // Default to HUF if not set
}

function getExchangeRate($currency) {
    global $pdo;
    
    // Base currency always has rate of 1
    if ($currency === 'EUR') return 1.00;
    
    // Try to get rate from database first
    $stmt = $pdo->prepare("SELECT rate FROM exchange_rates WHERE currency = ?");
    $stmt->execute([$currency]);
    $rate = $stmt->fetchColumn();
    
    if ($rate) {
        return (float)$rate;
    }
    
    // If not in database, try to get from config
    $currencyConfig = json_decode(file_get_contents(__DIR__ . '/../config/currencies.json'), true);
    if ($currencyConfig && isset($currencyConfig['currencies'][$currency]['default_rate'])) {
        return (float)$currencyConfig['currencies'][$currency]['default_rate'];
    }
    
    // Default to 1 if no rate found
    return 1.00;
}

function formatPrice($price, $forceCurrency = null) {
    global $currencyConfig;
    
    // Load currency configuration if not already loaded
    if (!isset($currencyConfig)) {
        $currencyConfig = json_decode(file_get_contents(__DIR__ . '/../config/currencies.json'), true);
        if (!$currencyConfig) {
            error_log('Error loading currency configuration in formatPrice');
            return $price;
        }
    }
    
    $displayCurrency = $forceCurrency ?? getShopCurrency();
    
    // If price is in EUR (base currency) and we want to display in another currency
    if ($displayCurrency !== $currencyConfig['base_currency']) {
        $rate = getExchangeRate($displayCurrency);
        $price = $price * $rate;
    }
    
    // Get currency info from config
    if (isset($currencyConfig['currencies'][$displayCurrency])) {
        $currencyInfo = $currencyConfig['currencies'][$displayCurrency];
        $symbol = $currencyInfo['symbol'];
        
        // Format based on step value (e.g., no decimals for HUF)
        $decimals = $currencyInfo['step'] === 100 ? 0 : 2;
        
        // Format with symbol in correct position
        if ($displayCurrency === 'HUF') {
            return number_format($price, $decimals, '.', ' ') . ' ' . $symbol;
        } else {
            return $symbol . number_format($price, $decimals, '.', ' ');
        }
    }
    
    // Fallback formatting if currency not in config
    return number_format($price, 2, '.', ' ') . ' ' . $displayCurrency;
}

// Helper function to convert price back to EUR for storage
function convertToEUR($price, $fromCurrency) {
    if ($fromCurrency === 'EUR') return $price;
    
    $rate = getExchangeRate($fromCurrency);
    return $rate > 0 ? ($price / $rate) : $price;
}

// Function to update user session data from database
function updateUserSession() {
    global $pdo;
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, name, email, user_role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['user_role'];
        } else {
            // User no longer exists in database
            session_destroy();
            header('Location: /ecommerce/login.php');
            exit();
        }
    }
}

function getSetting($pdo, $key, $default = null) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    
    return $value !== false ? $value : $default;
}