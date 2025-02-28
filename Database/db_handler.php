<?php
require_once __DIR__ . '/../RabbitMQ/RabbitMQServer.php';
require_once 'databaseConnect.php';

use RabbitMQ\RabbitMQServer;
use PhpAmqpLib\Message\AMQPMessage;

try {
    global $db;

    // Initialize RabbitMQServer (using "Database" from RabbitMQ.ini)
    $rbMQs = new RabbitMQServer('RabbitMQ/RabbitMQ.ini', 'Database');

    $rbMQs->consume(function ($message) use ($db) {
        echo "Message: $message\n";
    
        // Decode JSON message
        $data = json_decode($message, true);
        
        if (!isset($data['type'])) {
            echo "Error: Message type not specified.\n";
            return;
        }
    
        $type = $data['type'];
    
        // Registration handling
        if ($type === "register") {
            $email = $data['email'];
            $username = $data['username'];
            $password = $data['password'];
    
            // Check if the user already exists (either by email or username)
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $stmt->bind_result($userCount);
            $stmt->fetch();
            $stmt->close();
    
            if ($userCount > 0) {
                echo "Error: User with email '$email' or username '$username' already exists.\n";
            } else {
                // Insert data into database using prepared statements
                $stmt = $db->prepare("INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $email, $username, $password);
    
                if ($stmt->execute()) {
                    echo "User '$username' successfully registered and added to database.\n";
                } else {
                    echo "Error: " . $stmt->error . "\n";
                }
    
                $stmt->close();
            }
        // Login handling
        } elseif ($type === "login") {
            $email = $data['email'];
            $password = $data['password'];
    
            // Verify user credentials
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($hashedPassword);
            $stmt->fetch();
            $stmt->close();
    
            if ($hashedPassword) {
                if (password_verify($password, $hashedPassword)) { // Plaintext password compared against hash in DB
                    echo "Login successful for user '$email'.\n";
                } else {
                    echo "Error: Incorrect password for user '$email'.\n";
                }
            } else {
                echo "Error: User '$email' not found.\n";
            }
        } else {
            echo "Error: Unknown request type '$type'.\n";
        }
    });    

    // Close RabbitMQ connection when done
    $rbMQs->close();
} catch (Exception $error) {
    echo "Error: " . $error->getMessage() . "\n\n";
}
?>