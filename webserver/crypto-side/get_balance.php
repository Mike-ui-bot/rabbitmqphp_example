<?php
//include 'config.php';
require_once(__DIR__ . '/../../RabbitMQ/RabbitMQLib.inc');
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}
$username = $_SESSION['username'];
use RabbitMQ\RabbitMQClient;

$client = new RabbitMQClient(__DIR__ . '/../../RabbitMQ/RabbitMQ.ini', 'Database');

$balance_request = json_encode([
    'action' => 'get_balance',
    'username' => $username
]);
$balance_response = json_decode($client->sendRequest($balance_request), true);
$balance = $balance_response['status'] === 'success' ? $balance_response['balance'] : 0;

echo json_encode([
    'status' => 'success',
    'balance' => $balance
]);
?>



