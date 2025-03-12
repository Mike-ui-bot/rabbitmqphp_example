<?php
// config.php


$host = 'localhost';
$dbname = 'crypto_db';
$username = 'CryptoTest';
$password = 'coin';


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>




