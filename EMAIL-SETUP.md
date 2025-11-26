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
