<?php
require_once(__DIR__ . '/../RabbitMQ/RabbitMQLib.inc');

use RabbitMQ\RabbitMQServer;

$server = new RabbitMQServer(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'DMZ');

$server->consume(function($body, $properties, $channel) {
    $request = json_decode($body, true);

    switch ($request['action']) {
        case "getTop100Crypto":
            $apiUrl = "https://api.coincap.io/v2/assets";
            $response = file_get_contents($apiUrl);
            $data = json_decode($response, true);

            //Troubleshooting
            if ($data === null) {
                echo "Failed to parse API response.\n";
            } else {
                //echo "API response: " . print_r($data, true) . "\n";
            }

            $top100Crypto = array_slice($data['data'], 0, 100);
            return $top100Crypto;

        case "getCoinHistory":
            $coinId = $request['coinId'];
            $interval = $request['interval'];
            $apiUrl = "https://api.coincap.io/v2/assets/{$coinId}/history?interval={$interval}";

            $response = file_get_contents($apiUrl);
            $data = json_decode($response, true);

            return $data;
        
        default:
            return ["status" => "error", "message" => "Invalid action"];
    }
});
?>