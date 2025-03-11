<?php
// home.php
include 'config.php';
session_start();
if (!isset($_SESSION['username'])) {
    header(__DIR__ . '/../index.html'); // Redirect to login if no session
    exit();
}
$username = $_SESSION['username'];

// Get the last two recommended coins from the session
$recommended_coins = isset($_SESSION['recommended_coins']) ? $_SESSION['recommended_coins'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Website</title>
    <link rel="stylesheet" href="css/makeEverythingPretty.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Add Chart.js -->
    <script src="js/app.js" defer></script>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <a href="trade.php">Trade</a>
        <a href="portfolio.php">Portfolio</a>
        <a href="notifications.php">Notifications</a>
        <a href="rss.php">News</a>
    </div>

    <div class="nav-right">
        <span>Welcome, <?= htmlspecialchars($username); ?></span>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <h2>Live Market Cryptocurrencies</h2>
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

<!-- Modal for displaying the graph (hidden initially) -->
<div id="graphModal" style="display: none;">
    <div class="modal-content">
        <span id="closeModal">&times;</span>
        <h2>Price History of <span id="coin-name"></span></h2>
        <canvas id="coin-graph" width="400" height="200"></canvas>
    </div>
</div>

<!-- Keep an Eye On Section -->
<div class="container">
    <h2>Keep an Eye On</h2>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Name</th>
                <th>Price (USD)</th>
                <th>24h Change (%)</th>
            </tr>
        </thead>
        <tbody id="watchlist">
            <?php if (empty($recommended_coins)): ?>
                <tr><td colspan="4">No recommended coins yet.</td></tr>
            <?php else: ?>
                <?php foreach ($recommended_coins as $coin): ?>
                    <tr>
                        <td><?= htmlspecialchars($coin['rank']); ?></td>
                        <td><?= htmlspecialchars($coin['name']); ?> (<?= htmlspecialchars($coin['symbol']); ?>)</td>
                        <td>$<?= number_format($coin['priceUsd'], 2); ?></td>
                        <td><?= number_format($coin['changePercent24Hr'], 2); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="js/app.js"></script>
</body>
</html>
