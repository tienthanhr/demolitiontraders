<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="assets/css/new-style.css?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-container { max-width: 400px; margin: 60px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); padding: 32px; }
        .auth-tabs { display: flex; margin-bottom: 24px; }
        .auth-tab { flex: 1; text-align: center; padding: 12px 0; cursor: pointer; font-weight: 600; border-bottom: 2px solid #eee; transition: border 0.2s; }
        .auth-tab.active { border-bottom: 2px solid #2f3192; color: #2f3192; }
        .auth-form { display: none; }
        .auth-form.active { display: block; }
        .auth-form input { width: 100%; padding: 10px; margin-bottom: 16px; border-radius: 6px; border: 1px solid #ccc; }
        .auth-form button { width: 100%; padding: 12px; background: #2f3192; color: #fff; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .auth-form button:hover { background: #23246a; }
        .auth-error { color: #dc3545; margin-bottom: 12px; text-align: center; }
        .auth-success { color: #28a745; margin-bottom: 12px; text-align: center; }
        .password-field { position: relative; }
        .password-field input { padding-right: 40px; }
        .password-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666; font-size: 18px; }
        .password-toggle:hover { color: #2f3192; }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    <div class="auth-container">
        <div class="auth-tabs">
            <div class="auth-tab active" id="loginTab">Login</div>
            <div class="auth-tab" id="registerTab">Register</div>
        </div>
        <form id="loginForm" class="auth-form active">
            <div id="loginError" class="auth-error" style="display:none;"></div>
            <input type="email" name="email" placeholder="Email" required>
            <div class="password-field">
                <input type="password" id="loginPassword" name="password" placeholder="Password" required>
                <i class="fas fa-eye password-toggle" onclick="togglePassword('loginPassword', this)"></i>
            </div>
            <div style="text-align: right; margin-bottom: 12px;">
                <a href="#" id="forgotPasswordLink" style="color: #2f3192; font-size: 14px; text-decoration: none;">Forgot Password?</a>
            </div>
            <button type="submit">Login</button>
        </form>
        <form id="registerForm" class="auth-form">
            <div id="registerError" class="auth-error" style="display:none;"></div>
            <div id="registerSuccess" class="auth-success" style="display:none;"></div>
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="tel" name="phone" placeholder="Phone (optional)">
            <div class="password-field">
                <input type="password" id="registerPassword" name="password" placeholder="Password (min 8 characters)" required minlength="8">
                <i class="fas fa-eye password-toggle" onclick="togglePassword('registerPassword', this)"></i>
            </div>
            <div class="password-field">
                <input type="password" id="registerConfirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                <i class="fas fa-eye password-toggle" onclick="togglePassword('registerConfirmPassword', this)"></i>
            </div>
            <button type="submit">Register</button>
        </form>
        <form id="forgotPasswordForm" class="auth-form">
            <div id="forgotError" class="auth-error" style="display:none;"></div>
            <div id="forgotSuccess" class="auth-success" style="display:none;"></div>
            <p style="margin-bottom: 16px; color: #666;">Enter your email address and we'll send you a link to reset your password.</p>
            <input type="email" name="email" placeholder="Email" required>
            <button type="submit">Send Reset Link</button>
            <div style="text-align: center; margin-top: 12px;">
                <a href="#" id="backToLoginLink" style="color: #2f3192; font-size: 14px; text-decoration: none;">Back to Login</a>
            </div>
        </form>
    </div>
    <?php include '../components/footer.php'; ?>
    <?php include '../components/toast-notification.php'; ?>
    <script>`nconst BASE_PATH = '<?php echo BASE_PATH; ?>';
        // Toggle password visibility
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Tab switching
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const forgotPasswordForm = document.getElementById('forgotPasswordForm');
        const forgotPasswordLink = document.getElementById('forgotPasswordLink');
        const backToLoginLink = document.getElementById('backToLoginLink');
        
        loginTab.onclick = function() {
            loginTab.classList.add('active');
            registerTab.classList.remove('active');
            loginForm.classList.add('active');
            registerForm.classList.remove('active');
            forgotPasswordForm.classList.remove('active');
        };
        
        registerTab.onclick = function() {
            registerTab.classList.add('active');
            loginTab.classList.remove('active');
            registerForm.classList.add('active');
            loginForm.classList.remove('active');
            forgotPasswordForm.classList.remove('active');
        };
        
        forgotPasswordLink.onclick = function(e) {
            e.preventDefault();
            loginTab.classList.remove('active');
            registerTab.classList.remove('active');
            loginForm.classList.remove('active');
            registerForm.classList.remove('active');
            forgotPasswordForm.classList.add('active');
        };
        
        backToLoginLink.onclick = function(e) {
            e.preventDefault();
            loginTab.classList.add('active');
            loginForm.classList.add('active');
            forgotPasswordForm.classList.remove('active');
        };
        
        // Handle login
        loginForm.onsubmit = async function(e) {
            e.preventDefault();
            document.getElementById('loginError').style.display = 'none';
            const formData = new FormData(loginForm);
            const data = Object.fromEntries(formData);
            try {
                const res = await fetch(getApiUrl('/api/user/login.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    // Sync cart and wishlist after login
                    await syncCartOnLogin();
                    window.location.href = BASE_PATH + '.php';
                } else {
                    document.getElementById('loginError').textContent = result.message || 'Login failed';
                    document.getElementById('loginError').style.display = 'block';
                }
            } catch (err) {
                document.getElementById('loginError').textContent = 'Server error';
                document.getElementById('loginError').style.display = 'block';
            }
        };
        
        // Handle register
        registerForm.onsubmit = async function(e) {
            e.preventDefault();
            document.getElementById('registerError').style.display = 'none';
            document.getElementById('registerSuccess').style.display = 'none';
            const formData = new FormData(registerForm);
            const data = Object.fromEntries(formData);
            if (data.password !== data.confirm_password) {
                document.getElementById('registerError').textContent = 'Passwords do not match';
                document.getElementById('registerError').style.display = 'block';
                return;
            }
            try {
                const res = await fetch(getApiUrl('/api/user/register.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    document.getElementById('registerSuccess').textContent = 'Registration successful! Redirecting...';
                    document.getElementById('registerSuccess').style.display = 'block';
                    registerForm.reset();
                    // Auto redirect after successful registration (already logged in)
                    setTimeout(() => {
                        window.location.href = BASE_PATH + '.php';
                    }, 1500);
                } else {
                    document.getElementById('registerError').textContent = result.message || 'Registration failed';
                    document.getElementById('registerError').style.display = 'block';
                }
            } catch (err) {
                document.getElementById('registerError').textContent = 'Server error';
                document.getElementById('registerError').style.display = 'block';
            }
        };
        
        // Handle forgot password
        forgotPasswordForm.onsubmit = async function(e) {
            e.preventDefault();
            document.getElementById('forgotError').style.display = 'none';
            document.getElementById('forgotSuccess').style.display = 'none';
            
            const submitBtn = forgotPasswordForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading spinner
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            const formData = new FormData(forgotPasswordForm);
            const data = Object.fromEntries(formData);
            try {
                const res = await fetch(getApiUrl('/api/user/forgot-password.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    document.getElementById('forgotSuccess').textContent = result.message || 'Password reset link sent to your email!';
                    document.getElementById('forgotSuccess').style.display = 'block';
                    forgotPasswordForm.reset();
                } else {
                    document.getElementById('forgotError').textContent = result.message || 'Failed to send reset link';
                    document.getElementById('forgotError').style.display = 'block';
                }
            } catch (err) {
                document.getElementById('forgotError').textContent = 'Server error';
                document.getElementById('forgotError').style.display = 'block';
            } finally {
                // Restore button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        };
        
        // Sync cart from localStorage to database after login
        async function syncCartOnLogin() {
            const localCart = localStorage.getItem('cart');
            if (localCart) {
                try {
                    const cart = JSON.parse(localCart);
                    if (cart && Object.keys(cart).length > 0) {
                        await fetch(getApiUrl('/api/cart/sync.php'), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ cart })
                        });
                        localStorage.removeItem('cart');
                    }
                } catch (err) {
                    console.error('Cart sync error:', err);
                }
            }
        }
    </script>
</body>
</html>
