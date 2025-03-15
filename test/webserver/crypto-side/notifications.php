<?php
// notifications.php
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

