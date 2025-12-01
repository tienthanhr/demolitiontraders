# Hướng dẫn cài đặt và sử dụng hệ thống User Authentication

## Các tính năng đã triển khai

### 1. **Login & Register System** ✅
- Form đăng nhập và đăng ký tích hợp
- Validation email và password
- Password strength indicator
- Session management
- Auto-sync cart sau khi login

### 2. **Forgot Password** ✅
- Link "Forgot Password" trong form login
- Gửi email với reset token
- Token hết hạn sau 1 giờ
- Trang reset password với password strength indicator

### 3. **User Profile** ✅
- Xem và cập nhật thông tin cá nhân
- Xem order history
- Quản lý địa chỉ
- Đổi password

### 4. **Cart & Wishlist Sync** ✅
- Tự động đồng bộ cart từ localStorage sang database khi login
- Wishlist được lưu trong database cho logged-in users
- Dữ liệu được giữ nguyên khi user logout và login lại

### 5. **Admin Dashboard** ✅
- Quản lý danh sách users
- Thống kê tổng quan
- Search và filter users
- Reset password cho users
- Suspend/Activate user accounts

## Cài đặt

### Bước 1: Cập nhật Database Schema

Chạy file SQL để tạo bảng password reset tokens:

```sql
-- File: database/password_reset_tokens.sql
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;
```

Chạy lệnh trong MySQL:
```bash
mysql -u root -p demolitiontraders < database/password_reset_tokens.sql
```

### Bước 2: Cấu hình Email

Kiểm tra file `backend/config/email.php` để đảm bảo email configuration đúng.

### Bước 3: Test hệ thống

1. **Test Registration:**
   - Truy cập: `http://localhost/demolitiontraders/frontend/login.php`
   - Click tab "Register"
   - Điền thông tin và đăng ký

2. **Test Login:**
   - Sử dụng email và password vừa đăng ký
   - Kiểm tra cart có sync không

3. **Test Forgot Password:**
   - Click "Forgot Password"
   - Nhập email
   - Check email để lấy reset link

4. **Test User Profile:**
   - Sau khi login, truy cập: `http://localhost/demolitiontraders/frontend/profile.php`
   - Cập nhật thông tin
   - Xem order history
   - Đổi password

5. **Test Admin Dashboard:**
   - Login với admin account
   - Truy cập: `http://localhost/demolitiontraders/frontend/admin/users.php`
   - Quản lý users
   - Reset password cho users

## Cấu trúc Files

### Frontend Files
```
frontend/
├── login.php                 # Login/Register/Forgot Password form
├── reset-password.php        # Reset password page
├── profile.php               # User profile & order history
└── admin/
    └── users.php            # Admin user management
```

### Backend API Files
```
backend/api/
├── user/
│   ├── login.php            # Login API
│   ├── register.php         # Register API
│   ├── forgot-password.php  # Forgot password API
│   ├── reset-password.php   # Reset password API
│   ├── update-profile.php   # Update profile API
│   └── change-password.php  # Change password API
├── cart/
│   └── sync.php             # Cart sync API
└── admin/
    ├── reset-user-password.php    # Admin reset user password
    └── update-user-status.php     # Admin update user status
```

### Database Tables
```
users                    # User accounts
password_reset_tokens    # Password reset tokens
cart                     # User cart items
wishlist                # User wishlist
orders                  # Order history
addresses               # User addresses
```

## Security Features

1. **Password Hashing:** Sử dụng `password_hash()` với `PASSWORD_DEFAULT`
2. **Session Management:** Proper session handling với secure cookies
3. **SQL Injection Protection:** Prepared statements với PDO
4. **XSS Protection:** `htmlspecialchars()` cho outputs
5. **CSRF Protection:** Token-based validation (có thể thêm)
6. **Password Reset:** Time-limited tokens (1 hour)
7. **Admin Authorization:** Role-based access control

## Tính năng nâng cao có thể thêm

### 1. Email Verification
- Gửi email verification khi đăng ký
- User phải verify email trước khi login

### 2. Two-Factor Authentication (2FA)
- SMS hoặc authenticator app
- Tăng security cho admin accounts

### 3. Login History
- Theo dõi login attempts
- IP address và device tracking

### 4. Social Login
- Google OAuth
- Facebook Login

### 5. User Activity Log
- Admin xem được user activities
- Track changes to orders, profile, etc.

## Troubleshooting

### Email không gửi được:
- Kiểm tra SMTP configuration trong `backend/config/email.php`
- Nếu dùng localhost, có thể dùng services như Mailtrap hoặc Gmail SMTP

### Session không hoạt động:
- Kiểm tra `session_start()` được gọi ở đầu files
- Check PHP session configuration

### Cart không sync:
- Kiểm tra localStorage có data không
- Check console.log để debug
- Verify API endpoint hoạt động

### Database errors:
- Chắc chắn đã chạy migration script
- Check database credentials trong config

## API Endpoints Summary

### Public APIs
- `POST /api/user/register.php` - Register new user
- `POST /api/user/login.php` - Login user
- `POST /api/user/forgot-password.php` - Request password reset
- `POST /api/user/reset-password.php` - Reset password with token

### Authenticated User APIs
- `POST /api/user/update-profile.php` - Update user profile
- `POST /api/user/change-password.php` - Change password
- `POST /api/cart/sync.php` - Sync cart after login

### Admin APIs
- `POST /api/admin/reset-user-password.php` - Admin reset user password
- `POST /api/admin/update-user-status.php` - Admin update user status

## Liên hệ hỗ trợ

Nếu có vấn đề gì, hãy kiểm tra:
1. PHP error logs
2. Browser console
3. Network tab trong DevTools
4. Database query logs

Happy coding! 🚀
