<?php
require_once __DIR__ . '/../RabbitMQ/RabbitMQLib.inc';
require_once 'databaseConnect.php';

use RabbitMQ\RabbitMQServer;
use PhpAmqpLib\Message\AMQPMessage;

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
        
        if (!isset($data['type'])) {
            echo "Error: Message type not specified.\n";
            $response["message"] = "Message type not specified.";
        } else {
    
            $type = $data['type'];
        
            // Registration handling
            if ($type === "register") {
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
            } elseif ($type === "login") {
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
            } else {
                echo "Error: Unknown request type '$type'.\n";
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