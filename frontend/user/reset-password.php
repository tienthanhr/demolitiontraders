<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-container { max-width: 400px; margin: 60px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); padding: 32px; }
        .auth-form input { width: 100%; padding: 10px; margin-bottom: 16px; border-radius: 6px; border: 1px solid #ccc; }
        .auth-form button { width: 100%; padding: 12px; background: #2f3192; color: #fff; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .auth-form button:hover { background: #23246a; }
        .auth-form button:disabled { background: #ccc; cursor: not-allowed; }
        .auth-error { color: #dc3545; margin-bottom: 12px; text-align: center; }
        .auth-success { color: #28a745; margin-bottom: 12px; text-align: center; }
        .back-link { text-align: center; margin-top: 16px; }
        .back-link a { color: #2f3192; text-decoration: none; }
        .password-strength { font-size: 12px; margin-top: -12px; margin-bottom: 12px; }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    <div class="auth-container">
        <h2 style="text-align: center; margin-bottom: 24px;">Reset Password</h2>
        <div id="invalidToken" class="auth-error" style="display:none;">
            <p>Invalid or expired reset link. Please request a new password reset.</p>
        </div>
        <form id="resetPasswordForm" class="auth-form">
            <div id="resetError" class="auth-error" style="display:none;"></div>
            <div id="resetSuccess" class="auth-success" style="display:none;"></div>
            <input type="password" id="password" name="password" placeholder="New Password (min 8 characters)" required minlength="8">
            <div id="passwordStrength" class="password-strength"></div>
            <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit">Reset Password</button>
        </form>
        <div class="back-link">
            <a href="<?php echo userUrl('login.php'); ?>">Back to Login</a>
        </div>
    </div>
    <?php include '../components/footer.php'; ?>
    <?php include '../components/toast-notification.php'; ?>
    <script>
    const BASE_PATH = '<?php echo BASE_PATH; ?>';
        // Get token from URL
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        
        if (!token) {
            document.getElementById('invalidToken').style.display = 'block';
            document.getElementById('resetPasswordForm').style.display = 'none';
        }
        
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthDiv = document.getElementById('passwordStrength');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let message = '';
            let className = '';
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (password.length === 0) {
                message = '';
            } else if (strength < 2) {
                message = 'Weak password';
                className = 'strength-weak';
            } else if (strength < 3) {
                message = 'Medium password';
                className = 'strength-medium';
            } else {
                message = 'Strong password';
                className = 'strength-strong';
            }
            
            strengthDiv.textContent = message;
            strengthDiv.className = 'password-strength ' + className;
        });
        
        // Handle reset password
        document.getElementById('resetPasswordForm').onsubmit = async function(e) {
            e.preventDefault();
            document.getElementById('resetError').style.display = 'none';
            document.getElementById('resetSuccess').style.display = 'none';
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                document.getElementById('resetError').textContent = 'Passwords do not match';
                document.getElementById('resetError').style.display = 'block';
                return;
            }
            
            try {
                const res = await fetch(getApiUrl('/api/user/reset-password.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token, password })
                });
                
                const result = await res.json();
                
                if (result.success) {
                    document.getElementById('resetSuccess').textContent = result.message;
                    document.getElementById('resetSuccess').style.display = 'block';
                    document.getElementById('resetPasswordForm').reset();
                    
                    // Redirect to login after 2 seconds
                    setTimeout(() => {
                        window.location.href = BASE_PATH + 'login';
                    }, 2000);
                } else {
                    document.getElementById('resetError').textContent = result.message || 'Password reset failed';
                    document.getElementById('resetError').style.display = 'block';
                }
            } catch (err) {
                document.getElementById('resetError').textContent = 'Server error. Please try again.';
                document.getElementById('resetError').style.display = 'block';
            }
        };
    </script>
</body>
</html>
