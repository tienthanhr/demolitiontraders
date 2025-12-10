-- Add timestamps to track whether tax invoice / receipt have been sent already
ALTER TABLE orders
    ADD COLUMN tax_invoice_sent_at DATETIME NULL DEFAULT NULL,
    ADD COLUMN receipt_sent_at DATETIME NULL DEFAULT NULL;
