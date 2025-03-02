<?php
require_once __DIR__ . '/../RabbitMQ/RabbitMQLib.inc';

use RabbitMQ\RabbitMQClient;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';  
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        die("Error: Missing email or password.");
    }

    // Create login request as JSON
    $message = json_encode([
        "type" => "login",
        "email" => $email,
        'password' => $password
    ]);

    try {
        // Create RabbitMQ client and send message
        $client = new RabbitMQClient(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'Database');
        $response = $client->sendRequest($message);
        
        echo "Login request sent successfully.";
        echo $response;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
}
?>