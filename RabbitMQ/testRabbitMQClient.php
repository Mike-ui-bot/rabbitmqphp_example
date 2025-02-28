<?php
require_once(__DIR__ . '/../vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Load settings from RabbitMQ.ini
$config = parse_ini_file('RabbitMQ.ini', true);
$dbConfig = $config['Database']; // Using Database settings

class rabbitMQClient
{
    private $connection;
    private $channel;
    private $exchange;
    private $routing_key;

    public function __construct($config)
    {
        // Connect to RabbitMQ server
        $this->connection = new AMQPStreamConnection(
            $config['BROKER_HOST'],
            $config['BROKER_PORT'],
            $config['USER'],
            $config['PASSWORD'],
            $config['VHOST']
        );
        $this->channel = $this->connection->channel();
        $this->exchange = $config['EXCHANGE'];
        $this->routing_key = $config['ROUTING_KEY'];

        // Set exchange to type: topic and durable
        $this->channel->exchange_declare(
            $this->exchange,
            $config['EXCHANGE_TYPE'], 
            false,  
            true,   // Durable
            $config['AUTO_DELETE'] === 'true' ? true : false
        );
    }

    public function send_request($request)
    {
        $msg = new AMQPMessage(
            json_encode($request),
            ['delivery_mode' => 2] // Make message persistent
        );

        // Publish message to exchange with proper routing key
        $this->channel->basic_publish($msg, $this->exchange, $this->routing_key);

        // Messages in RabbitMQ are raw strings. Encode in JSON before sending
        echo "Sent: " . json_encode($request) . "\n";
    }

    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }
}

// Example usage
$client = new rabbitMQClient($dbConfig);

// Test request
$request = [
    "username" => "walter",
    "password" => "IT490",
    "message" => "Let me in!"
];

// Send the request
$client->send_request($request);

// Close connection
$client->close();
?>
