<?php
require_once '../config.php';
require_once '../components/date-helper.php';

// Check if user is admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    header('Location: ' . $protocol . $host . BASE_PATH . 'admin-login');
    exit;
}

require_once '../../backend/config/database.php';
$db = Database::getInstance();

// Get admin statistics
$stats = [
    'total_admins' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'] ?? 0,
    'total_customers' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")['count'] ?? 0,
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
];

// Get all admin users
$admins = $db->fetchAll(
    "SELECT u.*, 
     COUNT(DISTINCT o.id) as managed_orders
     FROM users u
     LEFT JOIN orders o ON o.updated_at >= u.created_at
     WHERE u.role = 'admin'
     GROUP BY u.id
     ORDER BY u.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="admin/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/api-helper.js"></script>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .stat-card i { font-size: 32px; color: #2f3192; margin-bottom: 10px; }
        .stat-card h3 { font-size: 28px; margin: 8px 0; color: #2f3192; }
        .stat-card p { color: #666; margin: 0; font-size: 14px; }
        .table-container { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #2f3192; }
        tr:hover { background: #f8f9fa; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-active { background: #28a745; color: white; }
        .badge-inactive { background: #ffc107; color: #333; }
        .badge-you { background: #2f3192; color: white; }
        .btn { padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: 600; }
        .btn-sm { padding: 4px 8px; font-size: 11px; }
        .btn-primary { background: #2f3192; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-header h2 { margin: 0; }
    </style>
    <script src="../assets/js/api-helper.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <?php include 'topbar.php'; ?>

<div class="content-section">
    <div class="section-header">
        <h2><i class="fas fa-user-shield"></i> Admin Management</h2>
        <button class="btn btn-success" onclick="showPromoteModal()">
            <i class="fas fa-plus"></i> Promote Customer to Admin
        </button>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card" onclick="filterUsers('admin')">
            <i class="fas fa-user-shield"></i>
            <h3><?php echo $stats['total_admins']; ?></h3>
            <p>Total Admins</p>
        </div>
        <div class="stat-card" onclick="window.location.href='admin/customers.php'">
            <i class="fas fa-users"></i>
            <h3><?php echo $stats['total_customers']; ?></h3>
            <p>Total Customers</p>
        </div>
        <div class="stat-card" onclick="showAllUsers()">
            <i class="fas fa-user-friends"></i>
            <h3><?php echo $stats['total_users']; ?></h3>
            <p>Total Users</p>
        </div>
    </div>

    <!-- Info Notice -->
    <div style="background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px 20px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-info-circle" style="color: #0c5460; font-size: 20px;"></i>
        <div style="flex: 1;">
            <strong style="color: #0c5460; font-size: 15px;">ℹ️ Undo Available:</strong>
            <p style="margin: 5px 0 0 0; color: #0c5460; font-size: 14px;">Deleted admins can be <strong>restored within 10 seconds</strong> using the Undo button.</p>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Admin Since</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admins)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px;">
                        <i class="fas fa-user-shield" style="font-size: 48px; color: #ccc; margin-bottom: 16px;"></i>
                        <p>No admin users found.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?php echo $admin['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></strong>
                            <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                <span class="badge badge-you">YOU</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td><?php echo htmlspecialchars($admin['phone'] ?? '-'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $admin['status']; ?>">
                                <?php echo ucfirst($admin['status']); ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($admin['created_at'], 'long'); ?></td>
                        <td><?php echo $admin['last_login'] ? formatDate($admin['last_login'], 'long') : 'Never'; ?></td>
                        <td>
                            <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-info" onclick="resetPassword(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>')" title="Reset Password">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="demoteAdmin(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>')" title="Demote to Customer">
                                    <i class="fas fa-arrow-down"></i> Demote
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm" disabled>
                                    <i class="fas fa-lock"></i> You
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="reset-password-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 35px; border-radius: 16px; max-width: 480px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                <i class="fas fa-key" style="color: white; font-size: 32px;"></i>
            </div>
            <h3 style="margin: 0 0 8px 0; color: #2f3192; font-size: 22px; font-weight: 600;">Reset Password</h3>
            <p id="reset-user-name" style="margin: 0; color: #666; font-size: 14px;"></p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px;">
                <i class="fas fa-lock"></i> New Password
            </label>
            <div style="position: relative;">
                <input type="password" id="new-password-input" 
                    placeholder="Enter new password (min 8 characters)"
                    style="width: 100%; padding: 12px 40px 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: all 0.3s;"
                    onfocus="this.style.borderColor='#667eea'" 
                    onblur="this.style.borderColor='#e0e0e0'">
                <i class="fas fa-eye" id="toggle-password-1" onclick="togglePasswordVisibility('new-password-input', 'toggle-password-1')" 
                    style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999;"></i>
            </div>
        </div>
        
        <div style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px;">
                <i class="fas fa-lock"></i> Confirm Password
            </label>
            <div style="position: relative;">
                <input type="password" id="confirm-password-input" 
                    placeholder="Re-enter password"
                    style="width: 100%; padding: 12px 40px 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: all 0.3s;"
                    onfocus="this.style.borderColor='#667eea'" 
                    onblur="this.style.borderColor='#e0e0e0'">
                <i class="fas fa-eye" id="toggle-password-2" onclick="togglePasswordVisibility('confirm-password-input', 'toggle-password-2')" 
                    style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999;"></i>
            </div>
        </div>
        
        <div id="password-error" style="display: none; padding: 10px 15px; background: #fee; border-left: 4px solid #dc3545; border-radius: 6px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle" style="color: #dc3545;"></i>
            <span style="color: #dc3545; font-size: 13px; margin-left: 8px;"></span>
        </div>
        
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button class="btn btn-secondary" onclick="closeResetPasswordModal()" style="padding: 12px 24px; border-radius: 8px;">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="btn btn-primary" onclick="submitResetPassword()" style="padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 8px;">
                <i class="fas fa-check"></i> Reset Password
            </button>
        </div>
    </div>
</div>

<script>
let resetPasswordUserId = null;
let resetPasswordUserName = null;

// Format date function for JavaScript
function formatDate(dateString, format = 'short') {
    if (!dateString) return 'Never';
    
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (format === 'short') {
        return date.toLocaleDateString('en-NZ', { year: 'numeric', month: 'short', day: 'numeric' });
    } else if (format === 'long') {
        return date.toLocaleDateString('en-NZ', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    } else {
        return date.toLocaleDateString('en-NZ');
    }
}

function filterUsers(role) {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        if (role === 'all') {
            row.style.display = '';
        } else if (role === 'admin') {
            // Already showing only admins
            row.style.display = '';
        }
    });
}

async function showAllUsers() {
    try {
        const res = await fetch(getApiUrl('/api/admin/all-users.php'));
        const text = await res.text();
        console.log('All users response:', text);
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            alert('Error: Server returned invalid response. Check console for details.');
            return;
        }
        
        if (result.success) {
            const modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;';
            
            const content = document.createElement('div');
            content.style.cssText = 'background:white;border-radius:12px;width:90%;max-width:1200px;max-height:90vh;overflow:auto;padding:30px;';
            
            let html = `
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <h2><i class="fas fa-users"></i> All Users (${result.users.length})</h2>
                    <button onclick="this.closest('[style*=fixed]').remove()" style="background:#dc3545;color:white;border:none;padding:8px 16px;border-radius:5px;cursor:pointer;font-size:18px;">✕ Close</button>
                </div>
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8f9fa;">
                            <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">ID</th>
                            <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Name</th>
                            <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Email</th>
                            <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Role</th>
                            <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Status</th>
                            <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Joined</th>
                            <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            result.users.forEach(user => {
                const roleBadge = user.role === 'admin' 
                    ? '<span style="background:#2f3192;color:white;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;">ADMIN</span>'
                    : '<span style="background:#28a745;color:white;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;">CUSTOMER</span>';
                
                const statusBadge = user.status === 'active'
                    ? '<span style="background:#28a745;color:white;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;">Active</span>'
                    : '<span style="background:#ffc107;color:#333;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;">Inactive</span>';
                
                html += `
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px;">${user.id}</td>
                        <td style="padding:12px;"><strong>${user.first_name} ${user.last_name}</strong></td>
                        <td style="padding:12px;">${user.email}</td>
                        <td style="padding:12px;">${roleBadge}</td>
                        <td style="padding:12px;">${statusBadge}</td>
                        <td style="padding:12px;">${formatDate(user.created_at)}</td>
                        <td style="padding:12px;">
                            <button onclick="viewUserPage('${user.role}', ${user.id})" style="background:#2f3192;color:white;border:none;padding:4px 8px;border-radius:5px;cursor:pointer;font-size:11px;">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += `</tbody></table>`;
            content.innerHTML = html;
            modal.appendChild(content);
            document.body.appendChild(modal);
        }
    } catch (err) {
        alert('Error loading users');
        console.error(err);
    }
}

function viewUserPage(role, userId) {
    if (role === 'admin') {
        window.location.href = 'admin/admins.php';
    } else {
        window.location.href = 'admin/customers.php';
    }
}

function showPromoteModal() {
    const email = prompt('Enter customer email to promote to Admin:');
    if (!email) return;
    
    promoteToAdmin(email);
}

async function promoteToAdmin(email) {
    if (!confirm(`⚠️ Are you sure you want to promote "${email}" to ADMIN?\n\nThey will have FULL access to admin panel!`)) return;
    
    try {
        const res = await fetch(getApiUrl('/api/admin/promote-to-admin.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        });
        
        const result = await res.json();
        
        if (result.success) {
            alert('✓ User promoted to Admin successfully!');
            location.reload();
        } else {
            alert('✗ Error: ' + result.message);
        }
    } catch (err) {
        alert('✗ Server error. Please try again.');
        console.error(err);
    }
}

function resetPassword(userId, userName) {
    resetPasswordUserId = userId;
    resetPasswordUserName = userName;
    
    document.getElementById('reset-user-name').textContent = `for ${userName}`;
    document.getElementById('new-password-input').value = '';
    document.getElementById('confirm-password-input').value = '';
    document.getElementById('password-error').style.display = 'none';
    
    const modal = document.getElementById('reset-password-modal');
    modal.style.display = 'flex';
}

function closeResetPasswordModal() {
    document.getElementById('reset-password-modal').style.display = 'none';
    resetPasswordUserId = null;
    resetPasswordUserName = null;
}

function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
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

async function submitResetPassword() {
    const newPassword = document.getElementById('new-password-input').value;
    const confirmPassword = document.getElementById('confirm-password-input').value;
    const errorDiv = document.getElementById('password-error');
    const errorSpan = errorDiv.querySelector('span');
    
    // Validate
    if (!newPassword || !confirmPassword) {
        errorSpan.textContent = 'Please fill in both password fields';
        errorDiv.style.display = 'block';
        return;
    }
    
    if (newPassword.length < 8) {
        errorSpan.textContent = 'Password must be at least 8 characters';
        errorDiv.style.display = 'block';
        return;
    }
    
    if (newPassword !== confirmPassword) {
        errorSpan.textContent = 'Passwords do not match';
        errorDiv.style.display = 'block';
        return;
    }
    
    errorDiv.style.display = 'none';
    
    try {
        const res = await fetch(getApiUrl('/api/admin/reset-user-password.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                user_id: resetPasswordUserId,
                new_password: newPassword
            })
        });
        
        const result = await res.json();
        
        if (result.success) {
            closeResetPasswordModal();
            alert('✓ ' + result.message);
        } else {
            errorSpan.textContent = result.message;
            errorDiv.style.display = 'block';
        }
    } catch (err) {
        errorSpan.textContent = 'Server error. Please try again.';
        errorDiv.style.display = 'block';
        console.error(err);
    }
}

async function demoteAdmin(adminId, adminName) {
    if (!confirm(`⚠️ Are you sure you want to DEMOTE "${adminName}" to Customer?\n\nThey will lose all admin privileges!`)) return;
    
    try {
        const res = await fetch(getApiUrl('/api/admin/update-user-role.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                user_id: adminId, 
                role: 'customer' 
            })
        });
        
        const result = await res.json();
        
        if (result.success) {
            alert('✓ Admin demoted to Customer successfully!');
            location.reload();
        } else {
            alert('✗ Error: ' + result.message);
        }
    } catch (err) {
        alert('✗ Server error. Please try again.');
        console.error(err);
    }
}
</script>
        </main>
    </div>
    <?php include '../components/toast-notification.php'; ?>
</body>
</html>
