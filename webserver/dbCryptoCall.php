<?php
// CORS headers should be at the top of the script to ensure they are sent
header("Access-Control-Allow-Origin: *");  // Allow all origins, or replace * with 'http://localhost:3000' for stricter security
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); // Allowed HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With"); // Allowed headers

// Handle preflight OPTIONS request (CORS preflight request)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Send a 200 response for preflight request
    http_response_code(200);
    exit();
}

require_once(__DIR__ . '/../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
use RabbitMQ\RabbitMQClient;

// Script for retrieving crypto data from the DB (NOT THE DMZ)
$client = new RabbitMQClient(__DIR__ . '/../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');

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

