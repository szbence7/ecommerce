<?php
// Get min and max prices from the database
require_once 'db.php';
require_once 'functions.php';

try {
    // Get prices in EUR from database
    $stmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products");
    $prices = $stmt->fetch(PDO::FETCH_ASSOC);
    $min_price_eur = floor($prices['min_price']);
    $max_price_eur = ceil($prices['max_price']);

    // Convert to display currency
    $currentCurrency = getShopCurrency();
    $rate = getExchangeRate($currentCurrency);
    $min_price = floor($min_price_eur * $rate);
    // Add a small buffer to max price to ensure inclusion of highest priced items
    $max_price = ceil($max_price_eur * $rate * 1.001);

    // Get current filter values from URL and convert back to display currency
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
    $min_price = floor($min_price / 100) * 100;
    $max_price = ceil($max_price / 100) * 100;
    $current_min = floor($current_min / 100) * 100;
    $current_max = ceil($current_max / 100) * 100;
}
?>

<div class="price-filter-widget">
    <h3>Price Filter</h3>
    <div class="price-range-slider">
        <input type="range" id="priceMin" class="range-min" min="<?= $min_price ?>" max="<?= $max_price ?>" value="<?= $current_min ?>" step="<?= $step ?>">
        <input type="range" id="priceMax" class="range-max" min="<?= $min_price ?>" max="<?= $max_price ?>" value="<?= $current_max ?>" step="<?= $step ?>">
    </div>
    <div class="price-inputs">
        <div>
            Min: <input type="number" id="minPrice" value="<?= $current_min ?>" min="<?= $min_price ?>" max="<?= $max_price ?>" step="<?= $step ?>"> <?= $currencySymbol ?>
        </div>
        <div>
            Max: <input type="number" id="maxPrice" value="<?= $current_max ?>" min="<?= $min_price ?>" max="<?= $max_price ?>" step="<?= $step ?>"> <?= $currencySymbol ?>
        </div>
    </div>
    <button id="applyPriceFilter" class="btn btn-primary mt-2">Apply Filter</button>
</div>

<style>
.price-filter-widget {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 20px;
}

.price-range-slider {
    margin: 15px 0;
    position: relative;
    height: 35px;
}

.price-range-slider input[type="range"] {
    position: absolute;
    width: 100%;
    -webkit-appearance: none;
    background: none;
    pointer-events: none;
}

.price-range-slider input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #007bff;
    cursor: pointer;
    margin-top: -8px;
    position: relative;
    z-index: 3;
    pointer-events: auto;
}

.price-range-slider input[type="range"]::-webkit-slider-runnable-track {
    width: 100%;
    height: 4px;
    background: #ddd;
    border-radius: 2px;
    z-index: 1;
}

.price-range-slider input[type="range"].range-min {
    z-index: 2;
}

.price-range-slider input[type="range"].range-max {
    z-index: 2;
}

.price-inputs {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
}

.price-inputs input {
    width: 80px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    const minSlider = document.getElementById('priceMin');
    const maxSlider = document.getElementById('priceMax');
    const applyButton = document.getElementById('applyPriceFilter');
    
    // Update input when slider changes
    minSlider.addEventListener('input', function() {
        minPriceInput.value = this.value;
        if (parseInt(maxSlider.value) < parseInt(this.value)) {
            maxSlider.value = this.value;
            maxPriceInput.value = this.value;
        }
    });
    
    maxSlider.addEventListener('input', function() {
        maxPriceInput.value = this.value;
        if (parseInt(minSlider.value) > parseInt(this.value)) {
            minSlider.value = this.value;
            minPriceInput.value = this.value;
        }
    });
    
    // Update slider when input changes
    minPriceInput.addEventListener('change', function() {
        minSlider.value = this.value;
        if (parseInt(maxPriceInput.value) < parseInt(this.value)) {
            maxSlider.value = this.value;
            maxPriceInput.value = this.value;
        }
    });
    
    maxPriceInput.addEventListener('change', function() {
        maxSlider.value = this.value;
        if (parseInt(minPriceInput.value) > parseInt(this.value)) {
            minSlider.value = this.value;
            minPriceInput.value = this.value;
        }
    });
    
    // Apply filter button
    applyButton.addEventListener('click', function() {
        const minPrice = minPriceInput.value;
        const maxPrice = maxPriceInput.value;
        
        // Create and dispatch custom event
        const event = new CustomEvent('priceRangeChanged', {
            detail: {
                minPrice: minPrice,
                maxPrice: maxPrice
            }
        });
        window.dispatchEvent(event);
    });
});
</script>
