<?php
// backend/api/test_email.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../services/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../services/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../services/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: text/html'); // Changed to HTML for better readability of debug output

echo "<pre>";
echo "Testing Email Configuration...\n";
echo "----------------------------\n";

// Allow overriding via query params
$host = $_GET['host'] ?? getenv('SMTP_HOST');
$port = $_GET['port'] ?? getenv('SMTP_PORT');
$user = $_GET['user'] ?? getenv('SMTP_USER');
$pass = $_GET['pass'] ?? getenv('SMTP_PASS');
$secure = $_GET['secure'] ?? getenv('SMTP_SECURE');
$from = $_GET['from'] ?? getenv('SMTP_FROM');

echo "Host: " . ($host ?: 'Not Set') . "\n";
echo "Port: " . ($port ?: 'Not Set') . "\n";
echo "User: " . ($user ?: 'Not Set') . "\n";
echo "Pass: " . ($pass ? '******' : 'Not Set') . "\n";
echo "Secure: " . ($secure ?: 'Not Set') . "\n";
echo "From: " . ($from ?: 'Not Set') . "\n";

echo "----------------------------\n";

// DNS Check
echo "DNS Resolution Check:\n";
$ips = gethostbynamel($host);
if ($ips) {
    echo "Resolved IPs for $host:\n";
    print_r($ips);
} else {
    echo "Failed to resolve IP for $host\n";
}
echo "----------------------------\n";

if (!$host || !$user || !$pass) {
    die("ERROR: Missing SMTP configuration variables.\n");
}

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION; // Enable verbose debug output
    $mail->Debugoutput = 'html'; // Output debug in HTML friendly format
    
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;
    $mail->Password   = $pass;
    $mail->SMTPSecure = $secure;
    $mail->Port       = $port;
    
    // Timeout settings
    $mail->Timeout = 10; // Timeout in seconds
    $mail->SMTPKeepAlive = false;

    // Recipients
    $mail->setFrom($from ?: $user, 'Test Sender');
    $mail->addAddress($user); // Send to self

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Railway';
    $mail->Body    = 'This is a test email to verify SMTP configuration on Railway. <b>It works!</b>';
    $mail->AltBody = 'This is a test email to verify SMTP configuration on Railway. It works!';

    echo "Attempting to send email...\n";
    $mail->send();
    echo "Message has been sent successfully!\n";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
echo "</pre>";
