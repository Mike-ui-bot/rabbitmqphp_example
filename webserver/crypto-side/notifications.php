<?php
// notifications.php
session_start();
if (!isset($_SESSION['username'])) {
    header(__DIR__ . '/../index.html');
    exit();
}
$username = $_SESSION['username'];

// Initialize alerts array if not already set
if (!isset($_SESSION['alerts'])) {
    $_SESSION['alerts'] = [];
}

// Function to send SMS via InstaSent API
function send_sms($phone_number, $message) {
    $api_key = 'issw_ynbyfhq9yhyp8efwhpfjyfdpdbanduuf1cn';  // Your InstaSent API token
    $api_url = 'https://www.instasent.com/api/v2/send-sms';

    // Prepare data for the POST request
    $data = [
        'api_key' => $api_key,
        'to' => $phone_number,      // Phone number to send SMS to
        'message' => $message,      // Message content
        'sender' => 'Crypto'
    ];

    // Initialize cURL session
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));  // Send data
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the request and get the response
    $response = curl_exec($ch);
    curl_close($ch);

    // Decode the response and check if SMS was sent successfully
    $response_data = json_decode($response, true);
    if (isset($response_data['status']) && $response_data['status'] === 'success') {
        return true;  // SMS sent successfully
    } else {
        // Log the response error message for debugging
        error_log('InstaSent error response: ' . print_r($response_data, true));
        return false;  // Failed to send SMS
    }
}

// Handle form submission to set an alert
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coin_symbol = $_POST['coin_symbol'];
    $phone_number = $_POST['phone_number'];

    // Add new alert to the session's alerts array
    $_SESSION['alerts'][] = [
        'coin_symbol' => $coin_symbol,
        'phone_number' => $phone_number,
        'username' => $username,
        'created_at' => date("Y-m-d H:i:s")
    ];

    // Message to send via SMS
    $message = "Alert set for $coin_symbol! You will be notified when the price changes.";

    // Send SMS using InstaSent API
    $sms_sent = send_sms($phone_number, $message);

    if ($sms_sent) {
        $message = "Alert set successfully! You will be notified via SMS.";
    } else {
        $message = "Failed to send SMS. Please try again.";
    }
}

// Fetch active alerts from the session
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

        <label for="phone_number">Your Phone Number:</label>
        <input type="text" name="phone_number" required>

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
                <th>Phone Number</th>
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
                        <td><?= htmlspecialchars($alert['phone_number']) ?></td>
                        <td><?= date("Y-m-d H:i", strtotime($alert['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

