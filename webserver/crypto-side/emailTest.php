<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '/var/www/rabbitmqphp_example/vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();                                            // Set mailer to use SMTP
    $mail->Host       = 'smtp.gmail.com';                         // Set the SMTP server to Gmail
    $mail->SMTPAuth   = true;                                     // Enable SMTP authentication
    $mail->Username   = 'mikebutryn123@gmail.com';                   // Gmail username
    $mail->Password   = 'chhurfaapxwlbwqo';                    // Gmail password (or use App Password)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;           // Enable TLS encryption
    $mail->Port       = 587;                                      // TCP port to connect to (587 for TLS)

    // Recipients
    $mail->setFrom('alertcrypto@gmail.com', 'Mailer');
    $mail->addAddress('Techegaray316@gmail.com', 'Joe User');      // Add a recipient

    // Content
    $mail->isHTML(true);                                          // Set email format to HTML
    $mail->Subject = 'Test Email via Gmail';
    $mail->Body    = 'This is a <b>test email</b> kinda.... [uploading hack]';

    $mail->send();                                                // Send the email
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>

