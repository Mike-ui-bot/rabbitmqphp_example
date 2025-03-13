<?php
require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use RabbitMQ\RabbitMQClient;
require '/var/www/rabbitmqphp_example/vendor/autoload.php';

// Function to send email via PHPMailer
function send_email($email, $message) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mikebutryn123@gmail.com';
        $mail->Password   = 'chhurfaapxwlbwqo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('alertcrypto@gmail.com', 'Crypto Alert');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Crypto Alert';
        $mail->Body    = $message;
        $mail->send();
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
    }
}

// Function to monitor price change
function check_price_change($coin_symbol, $email) {
    $client = new RabbitMQClient(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');
    $old_price = null;
    $attempts = 5;

    while ($attempts > 0) {
        $request = ['action' => 'get_coin_price', 'coin_symbol' => $coin_symbol];
        $response = $client->send_request($request);

        if ($response && isset($response['price'])) {
            $new_price = $response['price'];
            if ($old_price !== null && $old_price != $new_price) {
                $message = "The price of $coin_symbol changed from $old_price to $new_price.<br>";
                $message .= "Market Cap: {$response['market_cap']}<br>";
                $message .= "Supply: {$response['supply']}<br>Max Supply: {$response['max_supply']}<br>";
                $message .= "24h Volume: {$response['volume']}<br>Change (24h): {$response['change_percent']}%<br>";
                $message .= "Last Updated: {$response['last_updated']}";
                send_email($email, $message);
                return;
            }
            $old_price = $new_price;
        }
        sleep(60); // Wait 1 minute before checking again
        $attempts--;
    }
}

// Ensure script runs only when executed from CLI with arguments
if (isset($argv[1]) && isset($argv[2])) {
    check_price_change($argv[1], $argv[2]);
}

