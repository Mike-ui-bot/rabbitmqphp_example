<?php
require_once __DIR__ . '/../vendor/autoload.php'; //Ensure php-amqplib is installed

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;

try {
    // Connect to DB
    // Note: This is accessing the DB as root and assumes a local database named IT490 exists. The IT490 DB should have tables created from user_auth.sql
    $db = new mysqli("localhost", "tester_user", "testMe", "Testdata");

    // Change IP after mesage broker VM is designated 
    $connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test', 'testHost');
    $channel = $connection->channel();

    // Declare the queue and exchange 
    $queue_name = 'testQueue';
    $channel->queue_declare($queue_name, false, true, false, false);

    $exchange_name = 'testExchange';
    $channel->exchange_declare($exchange_name, 'topic', false, true, false);

    // Bind queue to exchange
    $channel->queue_bind($queue_name, $exchange_name, '*');

    echo "Waiting for registration requests...\n";

    // Process messages from queue
     $callback = function ($msg) use ($db) {
        // The producer's message is encoded in JSON; turn into associative array for further processing
        $data = json_decode($msg->body, true);
        $email = $data['email'];
        $username = $data['username'];
        $password = $data['password'];

        // Insert data into DB using prepared statements, getting errors if not doing it this way.
        $stmt = $db->prepare("INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $username, $password);

        if ($stmt->execute()) {
            echo "User $username successfully registered and added to database.\n";
        } else {
            echo "Error: " . $stmt->error . "\n";
        }

        $stmt->close();
    };

    $channel->basic_consume($queue_name, '', false, true, false, false, $callback);

    // Continuously listen for messages
    while (true) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();
} catch (Exception $error) {
    echo "Error: {$error->getMessage()}\n";
}
?>
