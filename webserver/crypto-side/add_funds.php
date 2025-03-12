<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
session_start();

if (!isset($_SESSION['username'])) {
    header(__DIR__ . '/../index.html'); // Redirect to login if no session
    exit();
}

$username = $_SESSION['username'];
$amount_to_add = 10000; // Amount of fake funds to add

// Create RabbitMQ client
$client = new RabbitMQClient(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');

// Send request to add funds
$request = [
    'type' => 'addFunds',
    'username' => $username,
    'amount' => $amount_to_add
];

$response = $client->send_request($request);

if ($response['success']) {
    header("Location: portfolio.php");
} else {
    echo "Failed to add funds.";
}
exit();
?>

