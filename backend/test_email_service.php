<?php
require_once __DIR__ . '/services/EmailService.php';

$service = new EmailService();
$toEmail = $argv[1] ?? 'info@demolitiontraders.co.nz';
$result = $service->sendEmail($toEmail, 'Test from EmailService', '<p>Hello from EmailService</p>');
var_export($result);
