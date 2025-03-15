<?php
session_start();

require_once(__DIR__ . '/../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');

use RabbitMQ\RabbitMQClient;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($username) || empty($password)) {
        echo "<script>alert('Error: All fields are required.'); window.history.back();</script>";
        exit();
    }
    
    $hashed_password = hash("sha256", $password);
    
    // Encode message as JSON
    $message = json_encode([
        'action' => "register",
        'email' => $email,
        'username' => $username,
        'password' => $password,
    ]);

    try {
        // Create RabbitMQ client and send request
        $client = new RabbitMQClient(__DIR__ . '/../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');
        $response = json_decode($client->sendRequest($message), true);
        
        // Handle duplicate emails and usernames
        if ($response['status'] === "success") { 
            header("Location: index.html?success=" . urlencode($response['message']));
        } elseif($response['status'] === "email_error") {
            header("Location: index.html?error=" . urlencode($response['message']));
        } elseif ($response['status'] === "username_error") {
            header("Location: index.html?error=" . urlencode($response['message']));
        } else {
            header("Location: index.html?error=" . urlencode($response['message']));
        }
    } catch (Exception $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request method.'); window.history.back();</script>";
}

function sendConfirmationEmail($email, $username) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.yourmailserver.com'; // Use your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com'; // SMTP username
        $mail->Password = 'your-email-password'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('no-reply@example.com', 'Your Website');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Please confirm your email address';
        $mail->Body    = 'Hello ' . htmlspecialchars($username) . ',<br><br>'
                        . 'Thank you for registering! Please confirm your email address by clicking the link below:<br>'
                        . '<a href="http://yourwebsite.com/confirm_email.php?email=' . urlencode($email) . '">Confirm Email</a>';

        $mail->send();
        echo 'Confirmation email has been sent.';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

