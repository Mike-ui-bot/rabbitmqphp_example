<?php
require_once __DIR__ . '/../RabbitMQ/RabbitMQLib.inc';
require_once 'databaseConnect.php';

use RabbitMQ\RabbitMQServer;
use PhpAmqpLib\Message\AMQPMessage;

// This script handles requests from the DMZ: update crypto database, update cache, etc.

// Use DMZ configuration
$server = new RabbitMQServer(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'DMZ');

?>