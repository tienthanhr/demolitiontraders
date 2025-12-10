<?php
// CLI utility to test SMTP connection and PHPMailer debug output
require_once __DIR__ . '/config/email.php';
require_once __DIR__ . '/services/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/services/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/services/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require __DIR__ . '/config/email.php';

$to = $argv[1] ?? ($config['dev_email'] ?? null);
if (!$to) {
    echo "Usage: php -f backend/test_smtp_debug.php recipient@example.com\n";
    exit(1);
}

echo "Using SMTP server: {$config['smtp_host']}:{$config['smtp_port']}\n";

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = $config['smtp_auth'];
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port = $config['smtp_port'];
    $mail->Timeout = 10;
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {
        // Output immediately to console for Railway logs
        fwrite(STDOUT, "[PHPMailer DEBUG-" . $level . "] " . trim($str) . "\n");
    };
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($to);
    $mail->Subject = 'SMTP Debug Test';
    $mail->Body = "This is a test email for debug purposes.\n";

    // Attempt to connect
    echo "Attempting SMTP connect...\n";
    if ($mail->smtpConnect()) {
        echo "smtpConnect() succeeded\n";
        $mail->smtpClose();
    } else {
        echo "smtpConnect() failed: " . $mail->ErrorInfo . "\n";
    }

    // Try sending an email (will actually send if server accepts it)
    echo "Attempting to send mail (may actually deliver)...\n";
    try {
        $success = $mail->send();
        echo "send() returned: " . ($success ? 'true' : 'false') . "\n";
    } catch (Exception $ex) {
        echo "send() Exception: " . $ex->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "PHPMailer setup or connection exception: " . $e->getMessage() . "\n";
}

echo "Done.\n";
