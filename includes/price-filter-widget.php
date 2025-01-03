<?php
// Get min and max prices from the database
require_once 'db.php';
require_once 'functions.php';
require_once 'language.php';

try {
    // Get prices in EUR from database
    $stmt = $pdo->query("SELECT MIN(CASE WHEN is_on_sale = 1 THEN discount_price ELSE price END) as min_price, 
                   MAX(CASE WHEN is_on_sale = 1 THEN discount_price ELSE price END) as max_price 
            FROM products");
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

// Set appropriate step based on price range for HUF
if ($currentCurrency === 'HUF') {
    if ($max_price >= 1000000) {
        $step = 10000; // 10,000 Ft steps for prices over 1M
    } elseif ($max_price >= 100000) {
        $step = 1000;  // 1,000 Ft steps for prices over 100K
    } elseif ($max_price >= 10000) {
        $step = 500;   // 500 Ft steps for prices over 10K
    } else {
        $step = 100;   // 100 Ft steps for lower prices
    }
} else {
    $step = 1; // Default step for other currencies
}

// Round values appropriately based on currency and step
if ($currentCurrency === 'HUF') {
    $min_price = round($min_price / $step) * $step;
    $max_price = round($max_price / $step) * $step;
    $current_min = round($current_min / $step) * $step;
    $current_max = round($current_max / $step) * $step;
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
                    <span id="minPriceLabel"><?= formatPrice($current_min) ?></span> -
                    <span id="maxPriceLabel"><?= formatPrice($current_max) ?></span>
                </div>
                <div class="slider-container">
                    <input type="range" class="range-min" id="priceMin" min="<?= $min_price ?>" max="<?= $max_price ?>" step="<?= $step ?>" value="<?= $current_min ?>">
                    <input type="range" class="range-max" id="priceMax" min="<?= $min_price ?>" max="<?= $max_price ?>" step="<?= $step ?>" value="<?= $current_max ?>">
                </div>
            </div>
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
    const step = <?= $step ?>;

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
        
        // Használjuk a PHP formatPrice függvényt AJAX-szal
        fetch(`format_price.php?price=${minPriceInput.value}`)
            .then(response => response.text())
            .then(formattedPrice => {
                minLabel.textContent = formattedPrice;
            });
            
        fetch(`format_price.php?price=${maxPriceInput.value}`)
            .then(response => response.text())
            .then(formattedPrice => {
                maxLabel.textContent = formattedPrice;
            });
    }

    function applyFilter() {
        const event = new CustomEvent('priceRangeChanged', {
            detail: {
                minPrice: minPriceInput.value,
                maxPrice: maxPriceInput.value
            }
        });
        window.dispatchEvent(event);
        
        // Frissítjük az URL-t és újratöltjük az oldalt
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('min_price', minPriceInput.value);
        urlParams.set('max_price', maxPriceInput.value);
        window.location.search = urlParams.toString();
    }

    // Update labels while dragging
    minPriceInput.addEventListener('input', updateLabels);
    maxPriceInput.addEventListener('input', updateLabels);

    // Apply filter when slider is released
    minPriceInput.addEventListener('change', applyFilter);
    maxPriceInput.addEventListener('change', applyFilter);
});
</script>
