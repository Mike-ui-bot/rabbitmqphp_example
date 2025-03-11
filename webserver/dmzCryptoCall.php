<?php
require_once(__DIR__ . '/../RabbitMQ/RabbitMQLib.inc');

use RabbitMQ\RabbitMQClient;

// Script for retrieving crypto data from the DMZ
$client = new RabbitMQClient(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'DMZ');

// Process incoming POST request
$requestBody = file_get_contents("php://input");

if (empty($requestBody)) {
    echo json_encode(["status" => "error", "message" => "No request body provided"]);
    exit();
}

$request = json_decode(trim($requestBody), true);

// Check if action is valid
if (!isset($request['action'])) {
    echo json_encode(["status" => "error", "message" => "No action provided"]);
    exit();
}

// Send the request to the DB handler
$response = $client->sendRequest($request);

echo $response;
?>
