-- Migration: Add email_logs table to store all outgoing emails for auditing
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    user_id INT NULL,
    type VARCHAR(64) NOT NULL,
    send_method VARCHAR(32) NOT NULL,
    to_email VARCHAR(255) NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NULL,
    status VARCHAR(32) NULL,
    error_message TEXT NULL,
    response TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
