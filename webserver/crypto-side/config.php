<?php
// config.php


$host = 'localhost'; // Change if using a remote DB
$dbname = 'crypto_db';
$username = 'CryptoTest'; // Set your database username
$password = 'coin'; // Set your database password


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>




