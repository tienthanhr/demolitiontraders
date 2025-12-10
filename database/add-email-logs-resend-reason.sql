-- Migration: Add resend_reason field to email_logs
ALTER TABLE email_logs ADD COLUMN IF NOT EXISTS resend_reason TEXT NULL;