<?php
// Get min and max prices from the database
require_once 'db.php';
require_once 'functions.php';
require_once 'language.php';

try {
    // Get prices in EUR from database
    $stmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products");
    $prices = $stmt->fetch(PDO::FETCH_ASSOC);
    $min_price = floor($prices['min_price']);
    $max_price = ceil($prices['max_price']);

    // Convert to display currency
    $currentCurrency = getShopCurrency();
    if ($currentCurrency !== 'EUR') {
        $rate = getExchangeRate($currentCurrency);
        $min_price = floor($min_price * $rate);
        $max_price = ceil($max_price * $rate * 1.001); // Add a small buffer
    }

    // Get current filter values from URL
    $current_min = isset($_GET['min_price']) ? floatval($_GET['min_price']) : $min_price;
    $current_max = isset($_GET['max_price']) ? floatval($_GET['max_price']) : $max_price;

} catch (PDOException $e) {
    $min_price = 0;
    $max_price = 1000000;
    $current_min = $min_price;
    $current_max = $max_price;
}

// Get currency symbol
$currencySymbol = '';
switch($currentCurrency) {
    case 'EUR':
        $currencySymbol = '€';
        break;
    case 'HUF':
        $currencySymbol = 'Ft';
        break;
    case 'USD':
        $currencySymbol = '$';
        break;
    default:
        $currencySymbol = $currentCurrency;
}

// Set appropriate step based on currency
$step = $currentCurrency === 'HUF' ? 100 : 1;

// Round values appropriately based on currency
if ($currentCurrency === 'HUF') {
    $min_price = round($min_price / 100) * 100;
    $max_price = round($max_price / 100) * 100;
    $current_min = round($current_min / 100) * 100;
    $current_max = round($current_max / 100) * 100;
}
?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><?= __t('filter.title') ?></h5>
    </div>
    <div class="card-body">
        <div class="price-filter">
            <div class="price-range-slider">
                <div class="values">
                    <span id="minPriceLabel"><?= number_format($current_min, 0, '.', ' ') . ' ' . $currencySymbol ?></span> -
                    <span id="maxPriceLabel"><?= number_format($current_max, 0, '.', ' ') . ' ' . $currencySymbol ?></span>
                </div>
                <div class="slider-container">
                    <input type="range" class="range-min" id="priceMin" min="<?= $min_price ?>" max="<?= $max_price ?>" step="<?= $step ?>" value="<?= $current_min ?>">
                    <input type="range" class="range-max" id="priceMax" min="<?= $min_price ?>" max="<?= $max_price ?>" step="<?= $step ?>" value="<?= $current_max ?>">
                </div>
            </div>
            <button id="applyFilter" class="btn btn-primary w-100 mt-3"><?= __t('filter.apply') ?></button>
        </div>
    </div>
</div>

<style>
.price-range-slider {
    padding: 10px 0;
}

.values {
    text-align: center;
    margin-bottom: 10px;
    font-weight: 500;
}

.slider-container {
    position: relative;
    height: 35px;
    margin: 0 10px;
}

.slider-container input[type="range"] {
    position: absolute;
    width: calc(100% - 20px);
    -webkit-appearance: none;
    pointer-events: none;
    background: none;
    left: 10px;
    height: 20px;
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #007bff;
    cursor: pointer;
    margin-top: -8px;
    pointer-events: auto;
    position: relative;
    z-index: 1;
}

input[type="range"]::-webkit-slider-runnable-track {
    width: 100%;
    height: 4px;
    background: #ddd;
    border-radius: 2px;
}

input[type="range"].range-min {
    z-index: 2;
}

input[type="range"].range-max {
    z-index: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const minPriceInput = document.getElementById('priceMin');
    const maxPriceInput = document.getElementById('priceMax');
    const minLabel = document.getElementById('minPriceLabel');
    const maxLabel = document.getElementById('maxPriceLabel');
    const applyButton = document.getElementById('applyFilter');
    const currencySymbol = '<?= $currencySymbol ?>';
    const step = <?= $step ?>;

    function formatPrice(price) {
        return Math.round(price).toLocaleString() + ' ' + currencySymbol;
    }

    function updateLabels() {
        const minVal = parseFloat(minPriceInput.value);
        const maxVal = parseFloat(maxPriceInput.value);
        
        if (minVal > maxVal) {
            if (this === minPriceInput) {
                maxPriceInput.value = minVal;
            } else {
                minPriceInput.value = maxVal;
            }
        }
        
        minLabel.textContent = formatPrice(parseFloat(minPriceInput.value));
        maxLabel.textContent = formatPrice(parseFloat(maxPriceInput.value));
    }

    minPriceInput.addEventListener('input', updateLabels);
    maxPriceInput.addEventListener('input', updateLabels);

    applyButton.addEventListener('click', function() {
        const event = new CustomEvent('priceRangeChanged', {
            detail: {
                minPrice: minPriceInput.value,
                maxPrice: maxPriceInput.value
            }
        });
        window.dispatchEvent(event);
    });
});
</script>
