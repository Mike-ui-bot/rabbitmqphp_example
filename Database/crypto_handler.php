<?php
require_once(__DIR__ . '/../RabbitMQ/RabbitMQLib.inc');
require_once 'databaseConnect.php'; 

use RabbitMQ\RabbitMQClient;

// Script for getting crypto data from the API via the DMZ
// Sends requests to DMZ to fetch and store crypto data
try {
    global $db;
    echo "Trying to connect to RabbitMQ...\n";

    $client = new RabbitMQClient(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'DMZ');

    $request = ['action' => 'getTop100Crypto'];
    $response = $client->sendRequest($request);

    // Decode JSON response if it is a string
    if (is_string($response)) {
        $response = json_decode($response, true);
    }

    if (is_array($response) && !empty($response)) {
        $top100Crypto = $response; 

        foreach ($top100Crypto as $coin) {
            $coinData = json_encode($coin);

            $stmt = $db->prepare("INSERT INTO stocks (asset_id, name, symbol, price, market_cap, supply, max_supply, volume, change_percent, data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                name = VALUES(name), symbol = VALUES(symbol), price = VALUES(price), market_cap = VALUES(market_cap),
                supply = VALUES(supply), max_supply = VALUES(max_supply), volume = VALUES(volume),
                change_percent = VALUES(change_percent), data = VALUES(data), last_updated = CURRENT_TIMESTAMP");

            $stmt->bind_param("ssssssssss", 
                $coin['id'], 
                $coin['name'], 
                $coin['symbol'], 
                $coin['priceUsd'], 
                $coin['marketCapUsd'],  
                $coin['supply'], 
                $coin['maxSupply'], 
                $coin['volumeUsd24Hr'], 
                $coin['changePercent24Hr'], 
                $coinData
            );

            if (!$stmt->execute()) {
                echo "Error saving coin '{$coin['name']}' to database: " . $stmt->error . "\n";
            }
            $stmt->close();
        }
        echo "Crypto data saved to database! Check the DB.\n";
    } else {
        echo "Error: No valid data received from DMZ.\n";
    }

} catch (Exception $error) {
    echo "Error: " . $error->getMessage() . "\n\n";
}
