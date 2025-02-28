<?php
require_once(__DIR__ . '/../vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Load settings from RabbitMQ.ini
$config = parse_ini_file('RabbitMQ.ini', true);
$dbConfig = $config['Database']; // Using Database settings

class rabbitMQServer
{
    private $connection;
    private $channel;
    private $exchange;
    private $queue;
    private $routing_key;

    public function __construct($config)
    {
        // Connection to RabbitMQ server
        $this->connection = new AMQPStreamConnection(
            $config['BROKER_HOST'],
            $config['BROKER_PORT'],
            $config['USER'],
            $config['PASSWORD'],
            $config['VHOST']
        );
        $this->channel = $this->connection->channel();

        // Setting exchange, queue, and routing key
        $this->exchange = $config['EXCHANGE'];
        $this->queue = $config['QUEUE'];
        $this->routing_key = $config['ROUTING_KEY'];

        // Declare the exchange, should be durable and type: topic
        $this->channel->exchange_declare(
            $this->exchange, 
            $config['EXCHANGE_TYPE'], 
            false, 
            true, // Durable
            $config['AUTO_DELETE'] === 'true' ? true : false // Do not auto delete
        );

        // Declare the queue, should be durable
        $this->channel->queue_declare(
            $this->queue,
            false, 
            true, 
            false, 
            false // do not auto delete
        );

        // Bind queue to exchange with proper routing key
        $this->channel->queue_bind($this->queue, $this->exchange, $this->routing_key);
    }

    public function process_messages()
    {
        echo "Waiting for messages...\n";

        // Set up a consumer 
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

        // Continuously wait for messages
        while ($this->channel->callbacks) {
            $this->channel->wait();
        }
    }

    // Close connection when script is stopped
    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }
}

// Example usage
$server = new rabbitMQServer($dbConfig);
$server->process_messages();
$server->close();
?>
