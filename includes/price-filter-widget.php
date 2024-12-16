<?php
// Get min and max prices from the database
require_once 'db.php';

try {
    $stmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products");
    $prices = $stmt->fetch(PDO::FETCH_ASSOC);
    $min_price = floor($prices['min_price']);
    $max_price = ceil($prices['max_price']);
} catch (PDOException $e) {
    $min_price = 0;
    $max_price = 1000000;
}

// Get current filter values from URL if they exist
$current_min = isset($_GET['min_price']) ? intval($_GET['min_price']) : $min_price;
$current_max = isset($_GET['max_price']) ? intval($_GET['max_price']) : $max_price;
?>

<div class="price-filter-widget">
    <h3>Price Filter</h3>
    <div class="price-range-slider">
        <input type="range" id="priceMin" class="range-min" min="<?= $min_price ?>" max="<?= $max_price ?>" value="<?= $current_min ?>" step="1">
        <input type="range" id="priceMax" class="range-max" min="<?= $min_price ?>" max="<?= $max_price ?>" value="<?= $current_max ?>" step="1">
    </div>
    <div class="price-inputs">
        <div>
            Min: <input type="number" id="minPrice" value="<?= $current_min ?>" min="<?= $min_price ?>" max="<?= $max_price ?>" step="1"> Ft
        </div>
        <div>
            Max: <input type="number" id="maxPrice" value="<?= $current_max ?>" min="<?= $min_price ?>" max="<?= $max_price ?>" step="1"> Ft
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
    -webkit-appearance: none;
    width: 100%;
    position: absolute;
    background: none;
    pointer-events: none;
}

.price-range-slider input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #007bff;
    cursor: pointer;
    margin-top: -8px;
    pointer-events: auto;
    position: relative;
    z-index: 1;
}

.price-range-slider input[type="range"]::-moz-range-thumb {
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #007bff;
    cursor: pointer;
    border: none;
    pointer-events: auto;
    position: relative;
    z-index: 1;
}

.price-range-slider input[type="range"]::-webkit-slider-runnable-track {
    width: 100%;
    height: 5px;
    background: #ddd;
    border-radius: 3px;
    border: none;
}

.price-range-slider input[type="range"]::-moz-range-track {
    width: 100%;
    height: 5px;
    background: #ddd;
    border-radius: 3px;
    border: none;
}

.price-inputs {
    margin: 10px 0;
}

.price-inputs input {
    width: 100px;
}

/* Active state for the track between the thumbs */
.price-range-slider input[type="range"].range-min {
    background: linear-gradient(to right, #ddd 0%, #007bff 100%);
}

.price-range-slider input[type="range"].range-max {
    background: linear-gradient(to right, #007bff 0%, #ddd 100%);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    const minSlider = document.getElementById('priceMin');
    const maxSlider = document.getElementById('priceMax');
    const applyButton = document.getElementById('applyPriceFilter');

    // Set initial values from URL if they exist
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('min_price') && urlParams.has('max_price')) {
        minPriceInput.value = urlParams.get('min_price');
        maxPriceInput.value = urlParams.get('max_price');
        minSlider.value = urlParams.get('min_price');
        maxSlider.value = urlParams.get('max_price');
    }

    // Update input when sliders change
    minSlider.addEventListener('input', function() {
        const minVal = parseInt(this.value);
        const maxVal = parseInt(maxSlider.value);
        
        if (minVal > maxVal) {
            maxSlider.value = minVal;
            maxPriceInput.value = minVal;
        }
        
        minPriceInput.value = minVal;
    });

    maxSlider.addEventListener('input', function() {
        const maxVal = parseInt(this.value);
        const minVal = parseInt(minSlider.value);
        
        if (maxVal < minVal) {
            minSlider.value = maxVal;
            minPriceInput.value = maxVal;
        }
        
        maxPriceInput.value = maxVal;
    });

    // Update sliders when inputs change
    minPriceInput.addEventListener('input', function() {
        const minVal = parseInt(this.value);
        const maxVal = parseInt(maxPriceInput.value);
        
        if (minVal > maxVal) {
            maxPriceInput.value = minVal;
            maxSlider.value = minVal;
        }
        
        minSlider.value = minVal;
    });

    maxPriceInput.addEventListener('input', function() {
        const maxVal = parseInt(this.value);
        const minVal = parseInt(minPriceInput.value);
        
        if (maxVal < minVal) {
            minPriceInput.value = maxVal;
            minSlider.value = maxVal;
        }
        
        maxSlider.value = maxVal;
    });

    // Apply filter button click handler
    applyButton.addEventListener('click', function() {
        const minPrice = parseInt(minPriceInput.value);
        const maxPrice = parseInt(maxPriceInput.value);
        
        if (minPrice > maxPrice) {
            alert('Minimum price cannot be greater than maximum price');
            return;
        }

        window.location.href = `?min_price=${minPrice}&max_price=${maxPrice}`;
    });
});
</script>
