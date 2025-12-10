# Email Configuration Guide

## Setup for Production

### 1. Gmail (Recommended for testing)

1. Enable 2-Factor Authentication on your Gmail account
2. Generate App Password:
   - Go to https://myaccount.google.com/security
   - Click "2-Step Verification"
   - Scroll down to "App passwords"
   - Select "Mail" and your device
   - Copy the 16-character password

3. Update `backend/config/email.php`:
```php
'smtp_host' => 'smtp.gmail.com',
'smtp_port' => 587,
'smtp_secure' => 'tls',
'smtp_username' => 'your-email@gmail.com',
'smtp_password' => 'your-16-char-app-password',
'from_email' => 'your-email@gmail.com',
'from_name' => 'Demolition Traders',
'dev_mode' => false, // Set to false for production
```

### 2. Office 365 / Outlook

```php
'smtp_host' => 'smtp.office365.com',
'smtp_port' => 587,
'smtp_secure' => 'tls',
'smtp_username' => 'admin@demolitiontraders.co.nz',
'smtp_password' => 'your-password',
```

### 3. Custom SMTP Server

```php
'smtp_host' => 'mail.yourdomain.com',
'smtp_port' => 587,
'smtp_secure' => 'tls',
'smtp_username' => 'admin@demolitiontraders.co.nz',
'smtp_password' => 'your-password',
```

## Development Mode

Keep `dev_mode => true` during development. All emails will be sent to `dev_email` instead of real customers.

```php
'dev_mode' => true,
'dev_email' => 'test@example.com',
```

## Preventing duplicate emails

- The system now records when tax-invoice and receipt emails are sent using `tax_invoice_sent_at` and `receipt_sent_at` fields on the `orders` table.
- By default, the API will not re-send an invoice or receipt for an order that already has a corresponding `*_sent_at` timestamp. Admins can explicitly force a re-send by passing `{ force: true }` in the JSON body of the send endpoint, or by using the admin UI which will ask for confirmation before forcing a re-send.
- To add the required database columns, run: `database/add-email-timestamps.sql` (ALTER TABLE to add `tax_invoice_sent_at` and `receipt_sent_at`).
 - To add the required database columns, run: `database/add-email-timestamps.sql` (ALTER TABLE to add `tax_invoice_sent_at` and `receipt_sent_at`).
 - To track all outgoing emails (audit trail), run: `database/add-email-logs.sql` which creates the `email_logs` table. This will log every outgoing email with status, method (SMTP/Brevo), and an associated order ID or user.

## Viewing Email Logs

- You can now view email logs per-order in the admin UI: click the history icon next to an order to open the email logs modal.
- For quick debugging you can also fetch logs via API: `GET /api/index.php?request=orders/{orderId}/email-logs` (admin-only). The UI already uses this endpoint.
 - Email logs now include a `resend_reason` field and the admin modal provides a 'Resend' action with an optional reason. The admin UI displays full details and lets you filter/search, view raw responses, and track resends for better auditability.
- A helper page is included for debugging: `backend/show_email_logs.php` (simple JSON output of recent logs). Make sure to remove / restrict this before deploying to production.

## Using Microsoft Exchange / Office365 (SMTP) notes

- If you are using Office365 (smtp.office365.com), ensure that `SMTP_USER` and `SMTP_FROM` are set in your `.env` and `SMTP_FROM` usually needs to match `SMTP_USER` unless the authenticated mailbox has SendAs/SendOnBehalf permission for the chosen from address.
- If you get errors like `SendAsDenied` then set `SMTP_FROM` to equal `SMTP_USER` or configure the mailbox/service account with the `SendAs` permission for the desired From address in Exchange Admin.
- For tenants where Basic SMTP Auth is disabled, consider using Microsoft Graph API based sending instead (OAuth client credentials + `Mail.Send` permission). If you want, I can implement Graph API support in `EmailService`.

## Testing

1. Create a test order
2. Check Apache error log: `C:\xampp\apache\logs\error.log`
3. Look for: "Tax Invoice sent to: email@example.com"

## Production Checklist

- [ ] Update SMTP credentials in `backend/config/email.php`
- [ ] Set `dev_mode => false`
- [ ] Update `from_email` to company email
- [ ] Test email delivery
- [ ] Check spam folder if emails not received
- [ ] Verify SPF/DKIM records for your domain (if using custom domain)

## Troubleshooting

**Error: "SMTP connect() failed"**
- Check SMTP credentials
- Verify firewall isn't blocking port 587/465
- Enable "Less secure app access" (Gmail) or use App Password

**Emails going to spam**
- Setup SPF record for your domain
- Setup DKIM signing
- Use domain email instead of Gmail

**No emails received**
- Check `enabled => true` in config
- Check error logs
- Verify email address is correct
- Test with different email provider

### Railway specific guidance

- Railway often restricts or blocks outbound SMTP connections on shared IPs for abuse prevention. If you are running on Railway and your app reports a successful send but recipients do not receive email, do the following:
   1. Set `SMTP_DEBUG=1` in Railway environment variables and view the service logs after triggering a send. The PHPMailer transcript will help identify connection, auth, and server responses.
   2. If PHPMailer connects and the SMTP server accepts the mail (250 2.0.0 OK), then the issue is likely delivery filtering (spam/quarantine) or Office365 configuration (SendAs/SendOnBehalf). Check the sending mailbox’s admin portal for quarantined messages.
   3. If PHPMailer fails to connect or authenticate from Railway but works locally, Railway may block the traffic; in that case use an API-based email provider (Brevo/Sendgrid/Mailgun) and set `BREVO_API_KEY` in Railway to enable the built-in fallback. The app will automatically attempt Brevo if SMTP fails.
   4. If you prefer SMTP and Railway blocks outbound email, contact Railway support to enable outbound SMTP for your project or use a relay service (e.g., SendGrid SMTP Relay) that Railway permits.
   5. For quick debugging, deploy the `backend/test_smtp_debug.php` script and run it on Railway using a Railway run/exec or via the admin run command to get debug output.

If you share the Railway service logs for a triggered send attempt (with `SMTP_DEBUG=1` enabled), I can help interpret the transcript and recommend next steps.
