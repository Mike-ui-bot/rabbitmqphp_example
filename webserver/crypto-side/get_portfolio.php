<?php
include 'config.php';
require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}
$username = $_SESSION['username'];
use RabbitMQ\RabbitMQClient;


$client = new RabbitMQClient(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');


$portfolio_request = json_encode([
    'action' => 'get_portfolio',
    'username' => $username
]);
$portfolio_response = json_decode($client->sendRequest($portfolio_request), true);
$portfolio = $portfolio_response['status'] === 'success' ? $portfolio_response['portfolio'] : 0;


echo json_encode([
    'status' => 'success',
    'portfolio' => $portfolio
]);
?>

