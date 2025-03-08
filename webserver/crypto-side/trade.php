<?php
// trade.php
include 'config.php';
session_start();
if (!isset($_SESSION['username'])) {
    header(__DIR__ . '/../index.html'); // Redirect to login if no session
    exit();
}
$username = $_SESSION['username'];

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
        <a href="browse.php">Browse Coins</a>
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
        <h2>Fake Buy/Sell Crypto</h2>
        <form id="trade-form">
            <label for="coin">Select Coin:</label>
            <input type="text" id="coin" placeholder="e.g., Bitcoin (BTC)">
            
            <label for="amount">Amount:</label>
            <input type="number" id="amount" step="0.0001" placeholder="Enter amount">
            
            <label>Type:</label>
            <input type="radio" name="trade-type" value="buy" checked> Buy
            <input type="radio" name="trade-type" value="sell"> Sell

            <button type="submit">Execute Trade</button>
        </form>
    </div>

    <script src="js/trade.js"></script>
</body>
</html>
