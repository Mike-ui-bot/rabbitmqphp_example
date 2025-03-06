<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // Redirect to login if no session
    exit();
}
$username = $_SESSION['username'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="Dashy.css">	
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script>
        // Fetch user session details from PHP
        fetch('login.php')
            .then(response => response.json())
            .then(data => {
                if (data.logged_in) {
                    document.getElementById("email").innerText = data.username;
                } else {
                    window.location.href = "login.html"; // Redirect if not logged in
                }
            });
    </script>
</head>
<body>
<div class="container">
        <div class="circle top-left"></div>
        <div class="circle top-right"></div>
        <div class="circle bottom-left"></div>
        <div class="circle bottom-right"></div>

    <h1>You logged in!</h1>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

    <!-- Logout Button -->
    <form action="logout.php" method="POST">
        <input type="submit" value="Logout">
    </form>
</div>
</body>
</html>

