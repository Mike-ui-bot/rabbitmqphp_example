<?php
session_start();

require_once __DIR__ . '/../RabbitMQ/RabbitMQClient.php'; // Includes php-ampqlib

use RabbitMQ\RabbitMQClient;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($username) || empty($password)) {
        echo "Error: All fields are required.";
        exit();
    }

    // Prepare data as JSON
    $message = json_encode([
        'type' => "register",
        'email' => $email,
        'username' => $username,
        'password' => password_hash($password, PASSWORD_BCRYPT)
    ]);

    try {
        // Create RabbitMQ client and send message
        $client = new RabbitMQClient('RabbitMQ/RabbitMQ.ini', 'Database');
        $client->publishMessage($message);
        $client->close();

        echo "Registration request sent successfully.";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
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
        $mail->addAddress($email); // Add recipient email

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

