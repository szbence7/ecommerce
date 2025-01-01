<?php
global $pdo;

$host = 'localhost';
$db   = 'ecommerce'; // vagy ami az adatbázisod neve
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=127.0.0.1;port=3306;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
} 