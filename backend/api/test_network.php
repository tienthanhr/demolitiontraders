<?php
// backend/api/test_network.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

$tests = [
    ['host' => 'google.com', 'port' => 80, 'name' => 'Google HTTP (80)'],
    ['host' => 'google.com', 'port' => 443, 'name' => 'Google HTTPS (443)'],
    ['host' => 'smtp.gmail.com', 'port' => 587, 'name' => 'Gmail SMTP (587)'],
    ['host' => 'smtp.gmail.com', 'port' => 465, 'name' => 'Gmail SMTP (465)'],
    ['host' => 'smtp.sendgrid.net', 'port' => 2525, 'name' => 'SendGrid SMTP (2525)'],
    ['host' => 'smtp-relay.brevo.com', 'port' => 587, 'name' => 'Brevo SMTP (587)'],
    ['host' => 'smtp.mailgun.org', 'port' => 2525, 'name' => 'Mailgun SMTP (2525)'],
];

echo "Network Connectivity Test\n";
echo "-------------------------\n";

foreach ($tests as $test) {
    echo "Testing {$test['name']} ({$test['host']}:{$test['port']})... ";
    $start = microtime(true);
    $fp = @fsockopen($test['host'], $test['port'], $errno, $errstr, 5);
    $end = microtime(true);
    $duration = round(($end - $start) * 1000, 2);
    
    if ($fp) {
        echo "SUCCESS ({$duration}ms)\n";
        fclose($fp);
    } else {
        echo "FAILED: $errstr ($errno)\n";
    }
}
