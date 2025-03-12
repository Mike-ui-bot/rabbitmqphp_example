<?php
ini_set('display_errors', 1);
include 'config.php';
require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../index.html');
    exit();
}
$username = $_SESSION['username'];
use RabbitMQ\RabbitMQClient;

$client = new RabbitMQClient(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');

// Fetch transactions
$transaction_request = json_encode([
    'type' => 'getTransactions',
    'username' => $username
]);
$transaction_response = json_decode($client->sendRequest($transaction_request), true);

$transactions = $transaction_response['status'] === 'success' ? $transaction_response['transactions'] : [];

$portfolio_request = json_encode([
    'type' => 'get_portfolio',
    'username' => $username
]);
$portfolio_response = json_decode($client->sendRequest($portfolio_request), true);
$portfolio = $portfolio_response['status'] === 'success' ? $portfolio_response['portfolio'] : [];

$balance_request = json_encode([
    'type' => 'get_balance',
    'username' => $username
]);
$balance_response = json_decode($client->sendRequest($balance_request), true);
$balance = $balance_response['status'] === 'success' ? $balance_response['balance'] : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade</title>
    <link rel="stylesheet" href="css/makeEverythingPretty.css">
    <script src="js/trade.js" defer></script>
</head>
<body>
<div class="navbar">
    <div class="nav-left">
        <a href="home.php">Home</a>
        <a href="portfolio.php">Portfolio</a>
        <a href="notifications.php">Notifications</a>
        <a href="rss.php">News</a>
    </div>
    <div class="nav-right">
        <span>Welcome, <?= htmlspecialchars($username); ?></span>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<!-- Trading Form -->
<div class="container">
    <h2>Trading Crypto</h2>
    <form id="trade-form">
        <label for="coin">Select Coin:</label>
        <input type="text" id="coin" placeholder="e.g., Bitcoin (BTC)" autocomplete="off">
        <div id="suggestions" class="suggestions-box"></div>

        <label for="amount">Amount:</label>
        <input type="number" id="amount" step="0.0001" placeholder="Enter amount">

        <label>Type:</label>
        <input type="radio" name="trade-type" value="buy" checked> Buy
        <input type="radio" name="trade-type" value="sell"> Sell

        <p>Total Price: <span id="total-price">$0.00</span></p>

        <button type="submit">Execute Trade</button>
    </form>
</div>

<!-- Transaction History -->
<div class="container">
    <h2>Past Transactions</h2>
    <table>
        <thead>
            <tr>
                <th>Coin</th>
                <th>Amount</th>
                <th>Price</th>
                <th>Type</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody id="transaction-history">
            <?php
            if ($transactions) {
                foreach ($transactions as $transaction) {
                    echo "<tr>
                            <td>{$transaction['coin_symbol']} ({$transaction['coin_name']})</td>
                            <td>{$transaction['amount']}</td>
                            <td>\${$transaction['price']}</td>
                            <td>{$transaction['type']}</td>
                            <td>{$transaction['timestamp']}</td>
                          </tr>";
                }
            }
            ?>
        </tbody>
    </table>
</div>

<script src="js/trade.js"></script>
</body>
</html>

