<?php
// notifications.php
include 'config.php';
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
<!-- Navigation Bar -->
    <div class="navbar">
        <a href="home.php">Home</a>
        <a href="browse.php">Browse Coins</a>
        <a href="trade.php">Trade</a>
	<a href="portfolio.php">Portfolio</a>
        <a href="TestDash.html">News</a>

    </div>

    <!-- Price Alert Form -->
    <div class="container">
        <h2>Set a Price Alert</h2>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="coin_symbol">Coin Symbol (e.g., BTC):</label>
            <input type="text" name="coin_symbol" required>

            <label for="alert_price">Alert Price (USD):</label>
            <input type="number" step="0.01" name="alert_price" required>

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
                    <th>Alert Price (USD)</th>
                    <th>Set On</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alerts)): ?>
                    <tr><td colspan="3">No active alerts.</td></tr>
                <?php else: ?>
                    <?php foreach ($alerts as $alert): ?>
                        <tr>
                            <td><?= $alert['coin_symbol'] ?></td>
                            <td>$<?= number_format($alert['alert_price'], 2) ?></td>
                            <td><?= date("Y-m-d H:i", strtotime($alert['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>

