<?php
require_once __DIR__ . '/../RabbitMQ/RabbitMQLib.inc';
require_once 'databaseConnect.php';

use RabbitMQ\RabbitMQServer;
use PhpAmqpLib\Message\AMQPMessage;

// This script handles internal database calls: authentication, user portfolio, etc.
try {
    global $db;
    echo "Trying to connect to RabbitMQ...\n";
    // Initialize RabbitMQServer (using "Database" from RabbitMQ.ini)
    $rbMQs = new RabbitMQServer(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'Database');

    $rbMQs->consume(function ($message) use ($db) {
        echo "Message: $message\n";
    
        // Decode JSON message
        $data = json_decode($message, true);
        $response = ["status" => "error", "message" => "Unknown error"];

        if (!isset($data['action'])) {
            echo "Error: Action not specified.\n";
            $response["message"] = "Action not specified.";
        } else {
    
            $action = $data['action'];
        
            // Registration handling
            if ($action === "register") {
                $email = $data['email'];
                $username = $data['username'];
                $password = $data['password'];
        
                // Check if email or username already exists
                $stmt = $db->prepare("SELECT email, username FROM users WHERE email = ? OR username = ?");
                $stmt->bind_param("ss", $email, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $existingUser = $result->fetch_assoc();
                $stmt->close();

                if ($existingUser) {
                    if ($existingUser['email'] === $email) {
                        echo "Error: Email '$email' is already in use.\n";
                        $response = ["status" => "email_error", "message" => "Email $email is already in use. Please use a different email."];
                    } elseif ($existingUser['username'] === $username) {
                        echo "Error: Username '$username' is already taken.\n";
                        $response = ["status" => "username_error", "message" => "Username $username is already taken. Please choose another."];
                    }
                } else {
                    // Insert data into database
                    $stmt = $db->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $email, $username, $password);

                    if ($stmt->execute()) {
                        echo "User '$username' successfully registered and added to database.\n";
                        $response = ["status" => "success", "message" => "Registration successful! Please log in."];
                    } else {
                        echo "Error: " . $stmt->error . "\n";
                        $response = ["status" => "error", "message" => "Sorry, we were unable to register you at this time."];
                    }
                    $stmt->close();
                }

            // Login handling
            } elseif ($action === "login") {
                $email = $data['email'];
                $password = $data['password'];
        
                // Verify user credentials
                $stmt = $db->prepare("SELECT username, password FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->bind_result($dbUsername, $dbPassword);
                $stmt->fetch();
                $stmt->close();
                  
                if ($password === $dbPassword) {
                   echo "Login successful for user '$email'.\n";
                   $response = ["status" => "success", "message" => "Login successful!", "username" => $dbUsername];
                } else {
                   echo "Error: Incorrect password for user '$email'.\n";
                   $response = ["status" => "error", "message" => "Invalid email or password."];
                } 
            } elseif ($action === "getTop100Crypto") {
                $query = "SELECT * FROM stocks ORDER BY market_cap DESC LIMIT 100"; // Example: Sort by market cap
                $result = $db->query($query);

                if ($result && $result->num_rows > 0) {
                    $cryptos = [];
                    while ($row = $result->fetch_assoc()) {
                        $cryptos[] = [
                            'id' => $row['asset_id'],
                            'name' => $row['name'],
                            'symbol' => $row['symbol'],
                            'priceUsd' => $row['price'],
                            'marketCapUsd' => $row['market_cap'],
                            'supply' => $row['supply'],
                            'maxSupply' => $row['max_supply'],
                            'volumeUsd24Hr' => $row['volume'],
                            'changePercent24Hr' => $row['change_percent'],
                        ];
                    }
                    $response = ["status" => "success", "data" => $cryptos];
                } else {
                    $response = ["status" => "error", "message" => "Failed to fetch top 100 cryptocurrencies from the database."];
                }
            } else {
                echo "Error: Unknown action '$action'.\n";
            }
        }
        return $response;
    });    
    // Close RabbitMQ connection when done
    $rbMQs->close();
} catch (Exception $error) {
    echo "Error: " . $error->getMessage() . "\n\n";
}
?>