<?php
/**
 * Email Configuration
 * Reads from .env or .env.local file - update to change email settings
 * On Render, use environment variables via the dashboard
 */

// Load .env.local file (for local development)
$envLocalFile = __DIR__ . '/../../.env.local';
if (file_exists($envLocalFile)) {
    $lines = file($envLocalFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!empty($name) && !isset($_ENV[$name])) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Load .env file (production)
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
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
    // SMTP Settings - from environment variables
    'smtp_host' => $_ENV['SMTP_HOST'] ?? 'smtp.office365.com',
    'smtp_port' => (int)($_ENV['SMTP_PORT'] ?? 587),
    'smtp_secure' => $_ENV['SMTP_SECURE'] ?? 'tls',
    'smtp_auth' => true,
    'smtp_username' => $_ENV['SMTP_USER'] ?? '',
    'smtp_password' => $_ENV['SMTP_PASS'] ?? '',
    
    // From Email (default to info@ for production)
    'from_email' => $_ENV['SMTP_FROM'] ?? $_ENV['SMTP_USER'] ?? 'info@demolitiontraders.co.nz',
    'from_name' => $_ENV['SMTP_FROM_NAME'] ?? 'Demolition Traders',
    
    // Reply To
    'reply_to' => $_ENV['SMTP_FROM'] ?? $_ENV['SMTP_USER'] ?? 'info@demolitiontraders.co.nz',
    
    // Development Mode - set to false in production
    'dev_mode' => getenv('APP_ENV') === 'development',
    'dev_email' => $_ENV['SMTP_USER'] ?? 'test@example.com',
    
    // Brevo API Key (Alternative to SMTP)
    'brevo_api_key' => $_ENV['BREVO_API_KEY'] ?? null,
    // Prefer Brevo over SMTP in production (1=true)
    'prefer_brevo' => (bool)($_ENV['PREFER_BREVO'] ?? false),
    // Force all outgoing mails to this from address (optional)
    'force_from_email' => $_ENV['FORCE_FROM_EMAIL'] ?? null,
    
    // Enable/Disable Email Sending
    'enabled' => true,
];
