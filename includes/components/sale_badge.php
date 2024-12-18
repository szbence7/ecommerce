<?php
function renderSaleBadge() {
    echo '<div class="sale-badge">SALE</div>';
}
?>

<style>
.product-image-container {
    position: relative;
    display: inline-block;
}

.sale-badge {
    position: absolute;
    top: 20px;
    left: -30px;
    background-color: #ff0000;
    color: white;
    padding: 5px 30px;
    transform: rotate(-45deg);
    font-weight: bold;
    font-size: 14px;
    text-transform: uppercase;
    z-index: 1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
</style>
