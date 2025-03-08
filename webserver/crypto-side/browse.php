<?php
// browse.php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Coins</title>
    <link rel="stylesheet" href="css/makeEverythingPretty.css">
    <script src="js/browse.js" defer></script>
</head>
<body>

    <!-- Navigation Bar -->
    <div class="navbar">
        <a href="home.php">Home</a>
        <a href="trade.php">Trade</a>
        <a href="portfolio.php">Portfolio</a>
	<a href="notifications.php">Notifications</a>
        <a href="TestDash.html">News</a>

    </div>

    <!-- Crypto List -->
    <div class="container">
        <h2>Browse Cryptocurrencies</h2>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Name</th>
                    <th>Price (USD)</th>
                    <th>24h Change (%)</th>
                </tr>
            </thead>
            <tbody id="crypto-list">
                <tr><td colspan="4">Loading...</td></tr>
            </tbody>
        </table>
    </div>

    <script src="js/app.js"></script>
</body>
</html>
