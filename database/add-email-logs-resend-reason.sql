-- Migration: Add resend_reason field to email_logs (MySQL)
ALTER TABLE email_logs ADD COLUMN resend_reason TEXT NULL;

-- PostgreSQL variant (if needed) - uncomment to run on Postgres
-- ALTER TABLE email_logs ADD COLUMN IF NOT EXISTS resend_reason TEXT NULL;