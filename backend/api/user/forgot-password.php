<?php
/**
 * Forgot Password API
 * Sends password reset link to user email
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../services/EmailService.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['email'])) {
        throw new Exception('Email is required');
    }
    
    $email = trim($data['email']);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    $db = Database::getInstance();
    
    // Check if user exists
    $user = $db->fetchOne(
        "SELECT id, first_name, email FROM users WHERE email = :email AND status = 'active'",
        ['email' => $email]
    );
    
    if (!$user) {
        // Email not found - tell user
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No account found with this email address. Please check your email or register a new account.'
        ]);
        exit;
    }
    
    // Generate unique token
    $token = bin2hex(random_bytes(32));
    
    // Save token to database with expiry time
    // Use PostgreSQL syntax: NOW() + INTERVAL '1 hour'
    // MySQL will also support this syntax
    $db->query(
        "INSERT INTO password_reset_tokens (user_id, token, expires_at, used) 
         VALUES (:user_id, :token, NOW() + INTERVAL '1 hour', 0)",
        [
            'user_id' => $user['id'],
            'token' => $token
        ]
    );
    
    // Generate reset link
    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/demolitiontraders/frontend/reset-password.php?token=" . $token;
    
    // Send email
    $subject = "Password Reset Request - Demolition Traders";
    $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2f3192; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { display: inline-block; padding: 12px 30px; background: #2f3192; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Password Reset Request</h2>
                </div>
                <div class='content'>
                    <p>Hi {$user['first_name']},</p>
                    <p>We received a request to reset your password for your Demolition Traders account.</p>
                    <p>Click the button below to reset your password:</p>
                    <p style='text-align: center;'>
                        <a href='{$resetLink}' class='button'>Reset Password</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; color: #2f3192;'>{$resetLink}</p>
                    <p><strong>This link will expire in 1 hour.</strong></p>
                    <p>If you didn't request a password reset, you can safely ignore this email.</p>
                    <p>Thanks,<br>Demolition Traders Team</p>
                </div>
                <div class='footer'>
                    <p>Demolition Traders<br>249 Kahikatea Drive, Hamilton<br>0800 DEMOLITION</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    // Send email using EmailService
    $emailService = new EmailService();
    $emailSent = $emailService->sendEmail($email, $subject, $message);
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Password reset link has been sent to your email. Please check your inbox and spam folder.'
        ]);
    } else {
        throw new Exception('Failed to send email. Please try again later.');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
