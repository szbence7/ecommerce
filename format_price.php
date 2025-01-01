<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');

if (isset($_GET['price'])) {
    $price = floatval($_GET['price']);
    echo formatPrice($price);
}
