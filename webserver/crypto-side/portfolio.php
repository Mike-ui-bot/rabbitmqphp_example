<?php
// portfolio.php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio</title>
    <link rel="stylesheet" href="css/makeEverythingPretty.css">
    <script src="js/portfolio.js" defer></script>
</head>
<body>
 <div class="navbar">
        <a href="home.php">Home</a>
        <a href="browse.php">Browse Coins</a>
	<a href="trade.php">Trade</a>
	<a href="notifications.php">Notifications</a>
        <a href="TestDash.html">News</a>

    </div>

    <div class="container">
        <h2>My Portfolio</h2>
        <table>
            <thead>
                <tr>
                    <th>Coin</th>
                    <th>Quantity</th>
                    <th>Avg. Price (USD)</th>
                    <th>Total Value (USD)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($portfolio as $coin): ?>
                    <tr>
                        <td><?= $coin['coin_name'] ?> (<?= $coin['coin_symbol'] ?>)</td>
                        <td><?= number_format($coin['quantity'], 4) ?></td>
                        <td>$<?= number_format($coin['average_price'], 2) ?></td>
                        <td>$<?= number_format($coin['quantity'] * $coin['average_price'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
