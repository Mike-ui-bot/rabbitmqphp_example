<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: dashboard.html");
    exit();
}

$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <h2>Welcome!</h2>
    <p>You logged in as: <strong><?php echo htmlspecialchars($email); ?></strong></p>

    <form action="logout.php" method="POST">
        <input type="submit" value="Logout">
    </form>
</body>
</html>

