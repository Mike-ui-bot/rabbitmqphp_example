<?php
require_once __DIR__ . '/../RabbitMQ/RabbitMQLib.inc';

use RabbitMQ\RabbitMQClient;

$client = new RabbitMQClient("RabbitMQ.ini", "Database");

$message = [
    "username" => "walter",
    "password" => "IT490",
    "message" => "Let me in!"
];

$client->publishMessage($message);
$client->close();
?>