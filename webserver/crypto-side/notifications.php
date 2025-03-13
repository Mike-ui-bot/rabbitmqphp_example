<?php
// notifications.php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../index.html');
    exit();
}
$username = $_SESSION['username'];

require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
use RabbitMQ\RabbitMQClient;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
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
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to check coin price over 5 minutes
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
                $message = "The price of $coin_symbol changed from $old_price to $new_price.\n";
                $message .= "Market Cap: {$response['market_cap']}\n";
                $message .= "Supply: {$response['supply']}\nMax Supply: {$response['max_supply']}\n";
                $message .= "24h Volume: {$response['volume']}\nChange (24h): {$response['change_percent']}%\n";
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

// Handle alert form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coin_symbol = $_POST['coin_symbol'];
    $email = $_POST['email'];
    $_SESSION['alerts'][] = [
        'coin_symbol' => $coin_symbol,
        'email' => $email,
        'username' => $username,
        'created_at' => date("Y-m-d H:i:s")
    ];
    
    send_email($email, "Alert set for $coin_symbol! You will be notified of price changes.");
    
    // Start background process for checking price
    exec("nohup php -r 'check_price_change(\"$coin_symbol\", \"$email\");' > /dev/null 2>&1 &");
}

// Fetch active alerts
$alerts = $_SESSION['alerts'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="css/makeEverythingPretty.css">
    <script src="js/notifications.js" defer></script>
</head>
<body>
<div class="navbar">
    <div class="nav-left">
        <a href="home.php">Home</a>
        <a href="trade.php">Trade</a>
        <a href="portfolio.php">Portfolio</a>
        <a href="rss.php">News</a>
    </div>

    <div class="nav-right">
        <span>Welcome, <?= htmlspecialchars($username); ?></span>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<!-- Set a Coin Alert Form -->
<div class="container">
    <h2>Set a Coin Alert</h2>
    <?php if (isset($message)): ?>
        <p style="color: green;"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="coin_symbol">Coin Symbol (e.g., BTC):</label>
        <input type="text" id="coin_symbol" name="coin_symbol" required>

        <div id="suggestions" class="suggestions-box"></div> 

        <label for="email">Your Email:</label>
        <input type="email" name="email" required> <!-- Change phone_number field to email -->

        <button type="submit">Set Alert</button>
    </form>
</div>

<!-- Active Alerts -->
<div class="container">
    <h2>My Active Alerts</h2>
    <table>
        <thead>
            <tr>
                <th>Coin</th>
                <th>Email</th> <!-- Change phone number to email -->
                <th>Set On</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($alerts)): ?>
                <tr><td colspan="3">No active alerts.</td></tr>
            <?php else: ?>
                <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td><?= htmlspecialchars($alert['coin_symbol']) ?></td>
                        <td><?= htmlspecialchars($alert['email']) ?></td> <!-- Change phone number to email -->
                        <td><?= date("Y-m-d H:i", strtotime($alert['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

