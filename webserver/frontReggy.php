<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php'; // Ensure php-amqplib is installed

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
$deliveryMode = AMQPMessage::DELIVERY_MODE_PERSISTENT;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get data from the form
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate the form data
    if ($password !== $confirm_password) {
        echo "Passwords do not match!";
        exit;
    }

    // For now, no password hashing, using the plain password
    $plain_password = $password;  

    try {
        // Connect to RabbitMQ
        $connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test', 'testHost');
        $channel = $connection->channel();

        // Declare the queue
        $queue_name = 'testQueue';
        $channel->queue_declare($queue_name, false, true, false, false);

        // Prepare user data to send to RabbitMQ
        $user_data = [
            'email' => $email,
            'username' => $username,
            'password' => $plain_password
        ];

        // Encode user data as JSON
        $message_body = json_encode($user_data);

        // Create the message to send to RabbitMQ
        $msg = new AMQPMessage(
            $message_body,
            [
                'content_type' => 'application/json',  // Set content type to JSON
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT // Ensure the message is persistent
            ]
        );

        // Send the message to the queue
        $channel->basic_publish($msg, '', $queue_name);

        // Confirm message sent
        echo "Registration request for user '$username' sent to the queue.";

        // Close the channel and connection
        $channel->close();
        $connection->close();
    } catch (Exception $error) {
        echo "Error: {$error->getMessage()}\n";
    }
} else {
    echo "Invalid request method.";
}
?>

