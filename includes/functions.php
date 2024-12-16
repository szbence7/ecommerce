<?php
function getShopCurrency() {
    global $pdo;
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'currency'");
    return $stmt->fetchColumn() ?: 'HUF';
}

function getExchangeRate($currency) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT rate FROM exchange_rates WHERE currency = ?");
    $stmt->execute([$currency]);
    return $stmt->fetchColumn() ?: 1.00;
}

function formatPrice($price) {
    $displayCurrency = getShopCurrency();
    $baseRate = getExchangeRate($displayCurrency);
    
    // Az ár átváltása a megjelenítendő pénznemre
    $convertedPrice = $price * $baseRate;
    
    switch ($displayCurrency) {
        case 'HUF':
            return number_format($convertedPrice, 0, '.', ' ') . ' Ft';
        case 'EUR':
            return '€' . number_format($convertedPrice, 2, '.', ' ');
        case 'USD':
            return '$' . number_format($convertedPrice, 2, '.', ' ');
        default:
            return number_format($convertedPrice, 2, '.', ' ') . ' ' . $displayCurrency;
    }
} 