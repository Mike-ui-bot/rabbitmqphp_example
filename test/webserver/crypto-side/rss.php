<?php

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
    <title>Crypto News Feed</title>
    <link rel="stylesheet" href="css/TestyDashy.css">
</head>
<body>
<div class="navbar">
    <div class="nav-left">
        <a href="home.php">Home</a>
        <a href="trade.php">Trade</a>
	<a href="portfolio.php">Portfolio</a>
        <a href="notifications.php">Notifications</a>
    </div>

    <div class="nav-right">
        <span>Welcome, <?= htmlspecialchars($username); ?></span>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</div>


    
    <div id="news-container"></div>
    <button id="load-more" onclick="loadMoreNews()">Load More</button>

    <script src="js/newsJuice.js"></script>
</body>
</html>

