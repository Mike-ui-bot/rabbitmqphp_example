<?php
// home.php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Website</title>
    <link rel="stylesheet" href="css/makeEverythingPretty.css">
    <script src="js/app.js" defer></script>
</head>
<body>
 <!-- Navigation Bar -->
    <div class="navbar">
        <a href="browse.php">Browse Coins</a>
        <a href="trade.php">Trade</a>
        <a href="portfolio.php">Portfolio</a>
	<a href="notifications.php">Notifications</a>
        <a href="TestDash.html">News</a>
    </div>

    <!-- Crypto Market Overview -->
    <div class="container">
        <h2>Live Cryptocurrency Prices</h2>
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


