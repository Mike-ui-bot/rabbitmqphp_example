<?php
require_once(__DIR__ . '/../RabbitMQ/RabbitMQLib.inc');

// USE ABSOLUTE PATH OR CRON WONT UPDATE DB
require_once('/var/www/test/Database/databaseConnect.php'); 

use RabbitMQ\RabbitMQClient;

// Script for getting crypto data from the API via the DMZ. Make sure dmz_handler.php is running

// logging for cron: this script runs every 5 minutes. check the log file to confirm
// USE ABSOLUTE PATH OR CRON WONT APPEND TO THE LOG FILE
$logFile = '/var/www/test/Database/crypto_handler_log.txt';

function logMessage($message) {
    global $logFile;
    $timestamp = date("Y-m-d H:i:s");
    $logEntry = "[$timestamp] $message";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

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
            $stmt = $db->prepare("INSERT INTO crypto (asset_id, name, symbol, price, market_cap, supply, max_supply, volume, change_percent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                name = VALUES(name), symbol = VALUES(symbol), price = VALUES(price), market_cap = VALUES(market_cap),
                supply = VALUES(supply), max_supply = VALUES(max_supply), volume = VALUES(volume),
                change_percent = VALUES(change_percent), last_updated = CURRENT_TIMESTAMP");

            $stmt->bind_param("sssssssss", 
                $coin['id'], 
                $coin['name'], 
                $coin['symbol'], 
                $coin['priceUsd'], 
                $coin['marketCapUsd'],  
                $coin['supply'], 
                $coin['maxSupply'], 
                $coin['volumeUsd24Hr'], 
                $coin['changePercent24Hr'], 
            );

            if (!$stmt->execute()) {
                echo "Error saving coin '{$coin['name']}' to database: " . $stmt->error . "\n";
                logMessage("Error: '{$coin['name']}' could not be saved to DB: " . $stmt->error . "\n");
            }
            $stmt->close();
        }
        echo "Crypto data successfully updated! Check the DB.\n";
        logMessage("Success: Crypto data successfully updated! Check the DB.\n");
    } else {
        echo "No valid data received from DMZ.\n";
        logMessage("Error: No valid data received from DMZ.\n");
    }
// RabbitMQ connection error
} catch (Exception $error) {
    echo "Error: " . $error->getMessage() . "\n\n";
}
