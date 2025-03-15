<?php
require_once(__DIR__ . '/../../RabbitMQ/RabbitMQLib.inc');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use RabbitMQ\RabbitMQClient;
require '/var/www/test/vendor/autoload.php';

echo "Starting script\n";

// Function to send email via PHPMailer
function send_email($email, $message) {
    $mail = new PHPMailer(true);
    echo "Sending email...\n";
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

        if ($mail->send()) {
            echo "Email sent!\n";
        } else {
            echo "Email not sent\n";
        }
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to monitor price change
function check_price_change($symbol, $email) {
    echo "Testing...\n";
    $client = new RabbitMQClient(__DIR__ . '/../../RabbitMQ/RabbitMQ.ini', 'Database');

    $request = ['action' => 'get_coin_price', 'symbol' => $symbol, 'email' => $email];
    $response = json_decode($client->sendRequest($request), true);

    // troubleshooting
    if (!$response) {
        echo "Failed to decode response\n";
        //var_dump($client->sendRequest($request));
        return;
    }

    if ($response && isset($response['price'])) {
        if ($response['price_changed']) {
            $old_price = $response['old_price'];
            $new_price = $response['price'];

            $message = "The price of $symbol changed from $old_price to $new_price.<br>";
            $message .= "Market Cap: {$response['market_cap']}<br>";
            $message .= "Supply: {$response['supply']}<br>Max Supply: {$response['max_supply']}<br>";
            $message .= "24h Volume: {$response['volume']}<br>Change (24h): {$response['change_percent']}%<br>";
            $message .= "Last Updated: {$response['last_updated']}";
    
            send_email($email, $message);
            echo "Email sent!\n";   
        } else {
            echo "No price change for $symbol.\n"; // Don't send email
        }
    } else {
        echo "Failed to retrieve price for $symbol\n";
        //var_dump($response);
    }
}

// Ensure script runs only when executed from CLI with arguments
if (isset($argv[1]) && isset($argv[2])) {
    $symbol = $argv[1];
    $email = $argv[2];

    while(true) {
        $emailSent = check_price_change($symbol, $email);
        if ($emailSent) {
            break;
        }
        sleep(30);
    }
} else {
    echo "Please enter the coin symbol and email as arguments\n";
    echo "Example: php check_price.php BTC test@email.com\n";
}

