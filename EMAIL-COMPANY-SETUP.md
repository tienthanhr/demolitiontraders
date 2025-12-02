# Email Setup Guide

## Đổi Email Cá Nhân sang Email Công Ty

### Bước 1: Lấy Thông Tin Email Công Ty

#### Nếu dùng **Office 365 / Microsoft 365**:

1. **Lấy SMTP Settings từ Outlook:**
   - Mở Outlook Desktop
   - File → Account Settings → Account Settings
   - Double-click vào email account
   - More Settings → Advanced tab
   - Xem Outgoing server (SMTP) settings

2. **Hoặc dùng settings chuẩn:**
   - SMTP Host: `smtp.office365.com`
   - Port: `587`
   - Security: `TLS`
   - Username: Email đầy đủ (VD: info@demolitiontraders.co.nz)
   - Password: Password email của bạn

3. **Nếu có 2-Factor Authentication:**
   - Vào https://account.microsoft.com/security
   - Tạo App Password cho "SMTP"
   - Dùng App Password thay vì password thông thường

#### Nếu dùng **Gmail Business (Google Workspace)**:

1. **Settings:**
   - SMTP Host: `smtp.gmail.com`
   - Port: `587`
   - Security: `TLS`
   - Username: Email đầy đủ
   - Password: App Password

2. **Tạo App Password:**
   - Vào https://myaccount.google.com/security
   - 2-Step Verification → App passwords
   - Chọn "Mail" và "Other device"
   - Copy App Password (16 ký tự)

#### Nếu dùng **cPanel / Generic Hosting**:

1. **Hỏi IT hoặc check cPanel:**
   - SMTP Host: Thường là `mail.yourdomain.com`
   - Port: `587` (TLS) hoặc `465` (SSL)
   - Security: `tls` hoặc `ssl`
   - Username: Email đầy đủ
   - Password: Email password

### Bước 2: Update File .env

Mở file `.env` trong thư mục gốc và sửa phần Email Configuration:

```env
# Email Configuration
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=info@demolitiontraders.co.nz
SMTP_PASS=your-password-here
SMTP_FROM=info@demolitiontraders.co.nz
SMTP_FROM_NAME=Demolition Traders
```

**Thay thế:**
- `SMTP_HOST`: SMTP server của bạn
- `SMTP_PORT`: Port (thường là 587)
- `SMTP_SECURE`: `tls` hoặc `ssl`
- `SMTP_USER`: Email đăng nhập
- `SMTP_PASS`: Password hoặc App Password
- `SMTP_FROM`: Email hiển thị khi gửi
- `SMTP_FROM_NAME`: Tên hiển thị

### Bước 3: Test Email

1. **Localhost:**
   - Khởi động lại XAMPP/Apache
   - Truy cập website
   - Test chức năng gửi email (đăng ký, forgot password, contact form)

2. **Production (Render):**
   - Commit và push changes:
     ```bash
     git add .env backend/config/email.php
     git commit -m "feat: configure email for Office 365"
     git push origin main
     ```
   - **QUAN TRỌNG:** Update Environment Variables trên Render:
     - Vào Render Dashboard
     - Chọn service → Environment
     - Add/Update các biến:
       - `SMTP_HOST`
       - `SMTP_PORT`
       - `SMTP_SECURE`
       - `SMTP_USER`
       - `SMTP_PASS`
       - `SMTP_FROM`
       - `SMTP_FROM_NAME`
   - Render sẽ tự động rebuild
   - Test email trên production

### Troubleshooting

#### Lỗi: "SMTP connect() failed"
- **Check:** SMTP Host và Port đúng chưa
- **Check:** Firewall có block port 587/465 không
- **Try:** Đổi port (587 ↔ 465) hoặc security (tls ↔ ssl)

#### Lỗi: "Authentication failed"
- **Check:** Username và Password đúng chưa
- **Check:** Có cần App Password không (nếu có 2FA)
- **Check:** Email account có enable SMTP/IMAP không

#### Lỗi: "Could not instantiate mail function"
- **Check:** PHP có extension `openssl` enabled không
- **Check:** File `php.ini` có uncomment `extension=openssl` chưa

#### Email gửi được nhưng vào Spam
- **Add:** SPF record trong DNS:
  ```
  v=spf1 include:spf.protection.outlook.com ~all
  ```
- **Add:** DKIM và DMARC records (hỏi IT department)

### Security Best Practices

1. **KHÔNG commit .env file** có thật vào Git
   - File `.gitignore` đã có `.env`
   - Chỉ commit `.env.example`

2. **Dùng App Password** thay vì password thật nếu có thể

3. **Trên Render:** Dùng Environment Variables, không hardcode

4. **Regular rotation:** Đổi password định kỳ

### Reference

- Office 365 SMTP: https://learn.microsoft.com/en-us/exchange/mail-flow-best-practices/how-to-set-up-a-multifunction-device-or-application-to-send-email-using-microsoft-365-or-office-365
- Gmail SMTP: https://support.google.com/mail/answer/7126229
- PHPMailer Docs: https://github.com/PHPMailer/PHPMailer

## Quick Setup for Office 365

```env
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@company.com
SMTP_PASS=your-password
SMTP_FROM=your-email@company.com
SMTP_FROM_NAME=Demolition Traders
```

Done! 🎉
