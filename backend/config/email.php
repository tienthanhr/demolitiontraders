<?php
/**
 * Email Configuration
 * Reads from .env file - update .env to change email settings
 */

// Load .env file
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!empty($name) && !isset($_ENV[$name])) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

return [
    // SMTP Settings - from .env
    'smtp_host' => $_ENV['SMTP_HOST'] ?? 'smtp.office365.com',
    'smtp_port' => (int)($_ENV['SMTP_PORT'] ?? 587),
    'smtp_secure' => $_ENV['SMTP_SECURE'] ?? 'tls',
    'smtp_auth' => true,
    'smtp_username' => $_ENV['SMTP_USER'] ?? '',
    'smtp_password' => $_ENV['SMTP_PASS'] ?? '',
    
    // From Email
    'from_email' => $_ENV['SMTP_FROM'] ?? $_ENV['SMTP_USER'] ?? '',
    'from_name' => $_ENV['SMTP_FROM_NAME'] ?? 'Demolition Traders',
    
    // Reply To
    'reply_to' => $_ENV['SMTP_FROM'] ?? $_ENV['SMTP_USER'] ?? '',
    
    // Development Mode - set to false in production
    'dev_mode' => false,
    'dev_email' => $_ENV['SMTP_USER'] ?? '',
    
    // Enable/Disable Email Sending
    'enabled' => true,
];
