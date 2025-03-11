<?php
// config.php


$host = 'localhost'; // Change if using a remote DB
$dbname = 'IT490';
$username = 'tester_user'; // Set your database username
$password = 'testMe'; // Set your database password


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>




