<?php
$to      = 'user@example.com';
$subject = 'Hello World';
$message = <<<MAIL
Hello There World,

This is a message to test sending messages.

Best regards,
Jupiter
MAIL;

$headers = 'From: TestApp keita753@gmail.com' . "\r\n" .
    'Reply-To: keita.tabuchi@firstloop-tech.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

mail($to, $subject, $message, $headers);