<?php
// Test SMTP via PHPMailer using backend/config/email.php settings
// Example usage via CLI: php backend/test_smtp_office365.php recipient@domain.com
// Example via web: http://localhost/demolitiontraders/backend/test_smtp_office365.php?to=recipient@domain.com

require_once __DIR__ . '/config/email.php';

// Load PHPMailer
require_once __DIR__ . '/services/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/services/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/services/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get config from loaded $config
$config = require __DIR__ . '/config/email.php';

$toParam = null;
if (php_sapi_name() === 'cli') {
    global $argv;
    if (!empty($argv[1])) $toParam = $argv[1];
} else {
    $toParam = $_GET['to'] ?? null;
}

$toEmail = $toParam ?: ($config['smtp_username'] ?? ($config['from_email'] ?? null));

if (!$toEmail) {
    echo "ERROR: No recipient specified and no SMTP username set in config.\n";
    exit(1);
}

function respond($ok, $message) {
    echo json_encode(['success' => (bool)$ok, 'message' => $message]);
    exit($ok ? 0 : 1);
}

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = $config['smtp_auth'];
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_secure'] ?? 'tls';
    $mail->Port = (int)($config['smtp_port'] ?? 587);
    // Add timeouts and skip peer verification if needed on local dev (copying setup behavior in EmailService)
    $mail->Timeout = 10;
    $debug = false;
    // Enable debug if ?debug=1 or CLI param --debug
    if (php_sapi_name() === 'cli') {
        global $argv;
        foreach ($argv as $arg) {
            if ($arg === '--debug' || $arg === '-d') $debug = true;
        }
    } else {
        if (isset($_GET['debug']) && ($_GET['debug'] == '1' || $_GET['debug'] == 'true')) $debug = true;
    }
    $mail->SMTPDebug = $debug ? 2 : 0;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($toEmail);
    $mail->Subject = 'SMTP Connectivity Test - Demolition Traders';
    $mail->isHTML(true);
    $mail->Body = '<p>This is a test message to verify sending through Microsoft Exchange / Office365 SMTP. If you got this, SMTP works.</p>';

    $ok = $mail->send();
    if ($ok) {
        error_log('[DemolitionTraders] test_smtp_office365.php: message sent to ' . $toEmail);
        respond(true, 'Message sent to: ' . $toEmail);
    } else {
        respond(false, 'Failed to send message but no exception (check SMTP logs)');
    }
} catch (Exception $e) {
    error_log('[DemolitionTraders] test_smtp_office365.php EX: ' . $e->getMessage());
    respond(false, 'Exception: ' . $e->getMessage());
}
