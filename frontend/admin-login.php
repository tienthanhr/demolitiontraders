<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    
    <!-- Load API Helper -->
    <script src="assets/js/api-helper.js?v=1"></script>
    
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .login-container h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2f3192;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2f3192;
        }
        .btn-login {
            width: 100%;
            padding: 15px;
            background: #2f3192;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
        }
        .btn-login:hover {
            background: #252675;
        }
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
        }
        .alert.show {
            display: block;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        .default-credentials {
            background: #fff3e0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .default-credentials strong {
            color: #e65100;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1><i class="fas fa-lock"></i> Admin Login</h1>
        
        <div class="default-credentials">
            <p><strong>Default Credentials:</strong></p>
            <p>Email: <code>admin@demolitiontraders.co.nz</code></p>
            <p>Password: <code>admin123</code></p>
        </div>
        
        <div id="alert" class="alert"></div>
        
        <form id="login-form">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" value="admin@demolitiontraders.co.nz" required>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-key"></i> Password</label>
                <input type="password" id="password" name="password" value="admin123" required>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="index.php">← Back to Homepage</a>
        </p>
    </div>
    
    <script>
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const alert = document.getElementById('alert');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Show loading
            alert.className = 'alert alert-success show';
            alert.textContent = 'Logging in...';
            
            try {
                const data = await apiFetch(getApiUrl('/api/user/login.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                
                if (data.success) {
                    // Check if user is admin
                    if (data.user && data.user.role === 'admin') {
                        alert.className = 'alert alert-success show';
                        alert.textContent = '✓ Login successful! Redirecting...';
                        
                        setTimeout(() => {
                            window.location.href = 'admin/index.php';
                        }, 1000);
                    } else {
                        alert.className = 'alert alert-error show';
                        alert.textContent = '✗ Access denied. Admin account required.';
                    }
                } else {
                    alert.className = 'alert alert-error show';
                    alert.textContent = '✗ ' + (data.message || 'Login failed');
                }
            } catch (error) {
                alert.className = 'alert alert-error show';
                alert.textContent = '✗ Connection error. Please try again.';
                console.error('Login error:', error);
            }
        });
    </script>
</body>
</html>
