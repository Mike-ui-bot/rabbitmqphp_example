<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

// Start the session
session_start();

// Using relative file path, you may have to change this
require_once(__DIR__ . '/../RabbitMQ/RabbitMQLib.inc');

use RabbitMQ\RabbitMQClient;

$email = isset($_POST['email']) ? $_POST['email'] : "";
$password = isset($_POST['password']) ? $_POST['password'] : "";

// Hash the password before sending it to RabbitMQ
$hashed_password = hash("sha256", $password);

// Prepare the request
$message = json_encode([
    'action' => 'login',
    'email' => $email,
    'password' => $password
]);

// Connect to RabbitMQ using Database configuration: using relative file path 
$client = new RabbitMQClient(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'Database');

// Only send request if email and password are set
if (!empty($email) && !empty($password)) {
    $response = json_decode($client->sendRequest($message), true);

    // Debugging: Check what the server responds with
    error_log("Login Response: " . json_encode($response));
    error_log("Received Response: " . json_encode($response));

    if ($response['status'] === "success") {
        // Success: Store user session data
        $_SESSION['email'] = $email;
        $_SESSION['session_key'] = session_id();
        $_SESSION['username'] = $response['username']; // Store retrieved username

        // Redirect to home.php
        header("Location: crypto-side/home.php");
        exit();
    } else {
        // Failure: Terminate session and show error message
        session_destroy();
        header("Location: index.html?error=" . urlencode($response['message']));
        exit();
    }
} else {
    // Missing email/password, redirect back with an error
    session_destroy();
    header("Location: index.html?error=" . urlencode("Email and password are required."));
    exit();
}
?>

