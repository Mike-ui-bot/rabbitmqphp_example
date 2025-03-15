<?php
// notifications.php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../index.html');
    exit();
}
$username = $_SESSION['username'];

require_once(__DIR__ . '/../../RabbitMQ/RabbitMQLib.inc');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use RabbitMQ\RabbitMQClient;
require '/var/www/test/vendor/autoload.php';

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

// Handle alert form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symbol = $_POST['symbol'];
    $email = $_POST['email'];

    $_SESSION['alerts'][] = [
        'symbol' => $symbol,
        'email' => $email,
        'username' => $username,
        'created_at' => date("Y-m-d H:i:s")
    ];

    // Send confirmation email when an alert is set
    send_email($email, "Alert set for $symbol! You will be notified of price changes.");

    // Start background process for checking price
    $check_price_script = __DIR__ . '/check_price.php';
    exec("nohup php $check_price_script $symbol $email > /dev/null 2>&1 &");
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
        <label for="symbol">Coin Symbol (e.g., BTC):</label>
        <input type="text" id="symbol" name="symbol" required>

        <div id="suggestions" class="suggestions-box"></div> 

        <label for="email">Your Email:</label>
        <input type="email" name="email" required> 

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
                <th>Email</th> 
                <th>Set On</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($alerts)): ?>
                <tr><td colspan="3">No active alerts.</td></tr>
            <?php else: ?>
                <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td><?= htmlspecialchars($alert['symbol']) ?></td>
                        <td><?= htmlspecialchars($alert['email']) ?></td> 
                        <td><?= date("Y-m-d H:i", strtotime($alert['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

