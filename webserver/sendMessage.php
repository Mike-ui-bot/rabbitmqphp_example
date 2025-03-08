<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once('/var/www/rabbitmqphp_example/path.inc');
require_once('/var/www/rabbitmqphp_example/get_host_info.inc');
require_once('/var/www/rabbitmqphp_example/rabbitMQLib.inc');

// Start the session
session_start();

$email = isset($_POST['email']) ? $_POST['email'] : "";
$password = isset($_POST['password']) ? $_POST['password'] : "";

// Hash the password before sending it to RabbitMQ
$hashed_password = hash("sha256", $password);

// Prepare the request
$request = array(
    'type' => 'login',
    'email' => $email,
    'password' => $password
);

// Connect to RabbitMQ
$client = new rabbitMQClient("/var/www/rabbitmqphp_example/testRabbitMQ.ini", "testServer");

// Only send request if email and password are set
if (!empty($email) && !empty($password)) {
    $response = $client->send_request($request);

    // Debugging: Check what the server responds with
    error_log("Login Response: " . json_encode($response));
error_log("Received Response: " . json_encode($response));

    if ($response['returnCode'] == 0) {
        // Success: Store user session data
        $_SESSION['email'] = $email;
        $_SESSION['session_key'] = session_id();
        $_SESSION['username'] = $response['username']; // Store retrieved username

        // Redirect to dashboard.php
        header("Location: dashboard.php");
        exit();
    } else {
        // Failure: Terminate session and show error message
        session_destroy();
        header("Location: index.html?error=" . urlencode("There was an error, try again"));
        exit();
    }
} else {
    // Missing email/password, redirect back with an error
    session_destroy();
    header("Location: index.html?error=" . urlencode("Email and password are required."));
    exit();
}
?>

