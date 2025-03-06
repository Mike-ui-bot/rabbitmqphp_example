<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include RabbitMQ dependencies
require_once('/var/www/rabbitmqphp_example/path.inc');
require_once('/var/www/rabbitmqphp_example/get_host_info.inc');
require_once('/var/www/rabbitmqphp_example/rabbitMQLib.inc');

// Database credentials
$DB_HOST = "25.3.237.152";
$DB_USER = "tester_user";
$DB_PASS = "testMe";
$DB_NAME = "IT490";

// Connect to MySQL database
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check for connection errors
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Function to process requests from sendMessage.php
function requestProcessor($request)
{
    global $mysqli;

    echo "Received request" . PHP_EOL;
    var_dump($request);

    if (!isset($request['type'])) {
        echo "Error: Unsupported message type received" . PHP_EOL;
        return array("returnCode" => '1', "message" => "ERROR: Unsupported message type");
    }

    if ($request['type'] == "login") {
        if (!empty($request['email']) && !empty($request['password'])) {
            $email = $mysqli->real_escape_string($request['email']);
            $hashed_password = $request['password']; // This is already hashed from sendMessage.php

            // Query to check if the user exists
            $query = "SELECT username, password FROM users WHERE email = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($username, $stored_hashed_password);
                $stmt->fetch();

                // Debugging: Log the stored and received hashes
                error_log("Stored Password: " . $stored_hashed_password);
                error_log("Received Password: " . $hashed_password);

                // Compare the stored hash with the received hash
                if ($hashed_password === $stored_hashed_password) {
                    echo "Login successful for: " . $email . PHP_EOL;
                    return array(
                        "returnCode" => '0',
                        "message" => "Valid credentials, redirecting...",
                        "username" => $username // Send username back to client
                    );
                } else {
                    echo "Login failed: Invalid credentials" . PHP_EOL;
                    return array("returnCode" => '1', "message" => "Invalid credentials");
                }
            } else {
                echo "Login failed: User not found" . PHP_EOL;
                return array("returnCode" => '1', "message" => "User not found");
            }
        }
    }

    echo "Unknown request type received" . PHP_EOL;
    return array("returnCode" => '1', "message" => "Unknown request type");
}

// Ensure RabbitMQ server is correctly initialized
$server = new rabbitMQServer("/var/www/rabbitmqphp_example/testRabbitMQ.ini", "testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

