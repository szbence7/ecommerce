<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');

$price = isset($_GET['price']) ? floatval($_GET['price']) : 0;
echo formatPrice($price);
