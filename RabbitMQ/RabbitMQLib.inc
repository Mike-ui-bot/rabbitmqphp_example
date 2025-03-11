<?php
namespace RabbitMQ;

require_once __DIR__ . '/../vendor/autoload.php'; // Ensure php-amqplib is installed

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

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

    // Works in a response/request manner
    public function consume(callable $callback) {
        $this->channel->basic_consume($this->queue, '', false, true, false, false, function ($msg) use ($callback) {
            echo "\nReceived message!\n";
            $properties = $msg->get_properties();
            print_r($properties);

            $response = $callback($msg->body, $properties, $this->channel);

            // If the response is valid, send it back
            if (!empty($properties['reply_to'])) {
                echo "Sending response to: " . $properties['reply_to'] . "\n";
                $replyMessage = new AMQPMessage(
                    json_encode($response),
                    ['correlation_id' => $properties['correlation_id']]
                );

                // Publish response back to the reply_to queue
                $this->channel->basic_publish($replyMessage, '', $properties['reply_to']);
                echo "Response sent successfully!\n";
            }
        });

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function close() {
        $this->channel->close();
        $this->connection->close();
    }

    // test
    public function process_messages()
    {
        $this->channel->basic_consume(
            $this->queue, // Queue name
            '',           // Consumer tag
            false,         // No local
            true,          // No ack (auto-acknowledge)
            false,         // Exclusive
            false,         // No wait
            function($msg) {
                echo "Received: ", $msg->body, "\n";
            }
        );

        while ($this->channel->callbacks) {
            $this->channel->wait();
        }
    }
}

class RabbitMQClient {
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
            throw new \Exception("Server '$server_section' not found in $ini_file.");
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

            // Declare exchange
            $this->channel->exchange_declare($this->exchange, $exchange_type, false, true, $auto_delete);

            // Declare queue (make sure it exists)
            $this->channel->queue_declare($this->queue, false, true, false, false);

            // Bind queue to exchange with routing key
            $this->channel->queue_bind($this->queue, $this->exchange, $this->routing_key);

        } catch (\Exception $e) {
            throw new \Exception("RabbitMQ Error: " . $e->getMessage());
        }
    }

    public function publishMessage($message) {
        if (is_array($message)) {
            $message = json_encode($message);
        }

        $msg = new AMQPMessage($message, ['delivery_mode' => 2]); // Persistent message
        $this->channel->basic_publish($msg, $this->exchange, $this->routing_key);
    }

    public function sendRequest($message) {
        if (is_array($message)) {
            $message = json_encode($message);
        }

        $correlation_id = uniqid();
        list($callback_queue, ,) = $this->channel->queue_declare("", false, false, true, false);

        $response = null;
        $this->channel->basic_consume($callback_queue, '', false, true, false, false,
            function ($msg) use (&$response, $correlation_id) {
                //echo "Received response with correlation ID: " . $msg->get('correlation_id') . "\n";
                if ($msg->get('correlation_id') == $correlation_id) {
                    $response = $msg->body;
                }
            }
        );

        $msg = new AMQPMessage(
            $message,
            [
                'correlation_id' => $correlation_id,
                'reply_to' => $callback_queue
            ]
        );

        $this->channel->basic_publish($msg, $this->exchange, $this->routing_key);

        //echo "Message sent with correlation ID: " . $correlation_id . "\n";
        //echo "Waiting for response...\n";

        // Timeout 
        $startTime = time();
        while (!$response) {
            $this->channel->wait();

            if(time() - $startTime > 5) {
                echo "Timeout waiting for response\n";
                return json_encode(["status" => "error", "message" => "No response from server."]);
            }
        }

        return $response;
    }

    public function close() {
        $this->channel->close();
        $this->connection->close();
    }
}