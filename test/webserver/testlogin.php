<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('Rabbit/path.inc');
require_once('Rabbit/get_host_info.inc');
require_once('Rabbit/rabbitMQLib.inc');

// Establish a connection to RabbitMQ
try {
    $client = new rabbitMQClient("Rabbit/testRabbitMQ.ini", "testServer");
    
    if (!$client) {
        die("Failed to connect to RabbitMQ.");
    }
    
    echo "Connected to RabbitMQ successfully.\n";

    // Send a test message
    $request = array(
        "type" => "test",
        "message" => "Hello from PHP!"
    );

    $response = $client->send_request($request);

    echo "Response from RabbitMQ: ";
    var_dump($response);
} catch (Exception $e) {
    echo "Error connecting to RabbitMQ: " . $e->getMessage();
}
?>

