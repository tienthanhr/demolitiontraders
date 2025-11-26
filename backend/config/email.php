<?php
/**
 * Email Configuration
 * Update these settings for production
 */

return [
    // SMTP Settings - Gmail
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_auth' => true,
    'smtp_username' => 'nguyenthanh123426@gmail.com',
    'smtp_password' => 'piygbjezehzjzixt',  // Remove spaces from app password
    
    // From Email
    'from_email' => 'nguyenthanh123426@gmail.com',
    'from_name' => 'Demolition Traders',
    
    // Reply To
    'reply_to' => 'nguyenthanh123426@gmail.com',
    
    // Development Mode - set to false in production
    'dev_mode' => false,
    'dev_email' => 'nguyenthanh123426@gmail.com', // Your email to receive test emails
    
    // Enable/Disable Email Sending
    'enabled' => true,
];
