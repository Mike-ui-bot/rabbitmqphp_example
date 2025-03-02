<?php
require_once __DIR__ . '/../RabbitMQ/RabbitMQLib.inc';

use RabbitMQ\RabbitMQServer;

$server = new RabbitMQServer("RabbitMQ.ini", "Database");
$server->process_messages();
$server->close();
?>