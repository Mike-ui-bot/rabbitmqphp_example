<?php
namespace RabbitMQ;

require_once __DIR__ . '/../vendor/autoload.php'; // Ensure php-amqplib is installed

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQServer {
    private $connection;
    private $channel;
    private $queue;
    private $exchange;
    private $routing_key;

    public function __construct($ini_file, $server_section) {
        if (!file_exists($ini_file)) {
            throw new \Exception("Configuration file $ini_file not found.");
        }

        $config = parse_ini_file($ini_file, true);
        if (!isset($config[$server_section])) {
            throw new \Exception("Section '$server_section' not found in $ini_file.");
        }

        $server_config = $config[$server_section];

        // RabbitMQ connection settings
        $host = $server_config['BROKER_HOST'];
        $port = $server_config['BROKER_PORT'];
        $user = $server_config['USER'];
        $password = $server_config['PASSWORD'];
        $vhost = $server_config['VHOST'];
        $this->exchange = $server_config['EXCHANGE'];
        $this->queue = $server_config['QUEUE'];
        $this->routing_key = $server_config['ROUTING_KEY'];
        $exchange_type = $server_config['EXCHANGE_TYPE'] ?? 'topic'; // Default to topic exchange
        $auto_delete = filter_var($server_config['AUTO_DELETE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

        try {
            // Establish connection to RabbitMQ
            $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
            $this->channel = $this->connection->channel();

            // Declare exchange and queue
            $this->channel->exchange_declare($this->exchange, $exchange_type, false, true, $auto_delete);
            $this->channel->queue_declare($this->queue, false, true, false, false);

            // Bind queue to exchange using routing key
            $this->channel->queue_bind($this->queue, $this->exchange, $this->routing_key);

            echo "Waiting for messages on queue '{$this->queue}'. \n";
        } catch (\Exception $e) {
            throw new \Exception("RabbitMQ Error: " . $e->getMessage());
        }
    }

    public function consume(callable $callback) {
        $this->channel->basic_consume($this->queue, '', false, true, false, false, function ($msg) use ($callback) {
            echo "\nReceived message!\n";
            $callback($msg->body);
        });

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function close() {
        $this->channel->close();
        $this->connection->close();
    }
}