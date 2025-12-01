<?php
require_once 'components/date-helper.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../backend/config/database.php';
$db = Database::getInstance();

// Get user info
$user = $db->fetchOne(
    "SELECT * FROM users WHERE id = :id",
    ['id' => $_SESSION['user_id']]
);

// Get user addresses
$addresses = $db->fetchAll(
    "SELECT * FROM addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC",
    ['user_id' => $_SESSION['user_id']]
);

// Get recent orders
$orders = $db->fetchAll(
    "SELECT o.*, COUNT(oi.id) as item_count 
     FROM orders o 
     LEFT JOIN order_items oi ON o.id = oi.order_id 
     WHERE o.user_id = :user_id 
     GROUP BY o.id 
     ORDER BY o.created_at DESC 
     LIMIT 10",
    ['user_id' => $_SESSION['user_id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Demolition Traders</title>
    <base href="/demolitiontraders/frontend/">
    <link rel="stylesheet" href="assets/css/new-style.css?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .account-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .account-header { margin-bottom: 30px; }
        .account-header h1 { color: #2f3192; margin-bottom: 10px; }
        .account-tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #eee; flex-wrap: wrap; }
        .account-tab { padding: 12px 24px; cursor: pointer; border-bottom: 3px solid transparent; font-weight: 600; color: #666; transition: all 0.3s; }
        .account-tab:hover { color: #2f3192; }
        .account-tab.active { color: #2f3192; border-bottom-color: #2f3192; }
        .account-content { display: none; }
        .account-content.active { display: block; }
        .info-card { background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .info-card h3 { color: #2f3192; margin-bottom: 16px; }
        .info-row { display: flex; margin-bottom: 12px; }
        .info-label { font-weight: 600; width: 150px; color: #666; }
        .info-value { flex: 1; }
        .btn { display: inline-block; padding: 10px 20px; background: #2f3192; color: #fff; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: 600; }
        .btn:hover { background: #23246a; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .orders-table { width: 100%; border-collapse: collapse; }
        .orders-table th, .orders-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .orders-table th { background: #f8f9fa; font-weight: 600; color: #2f3192; }
        .orders-table tr:hover { background: #f8f9fa; }
        .status-badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d1ecf1; color: #0c5460; }
        .status-processing { background: #cfe2ff; color: #084298; }
        .status-shipped { background: #d3d3d3; color: #383d41; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .address-card { background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 12px; position: relative; }
        .address-card.default { border: 2px solid #2f3192; }
        .default-badge { position: absolute; top: 10px; right: 10px; background: #2f3192; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
        .empty-state i { font-size: 48px; color: #ccc; margin-bottom: 16px; }
        input, textarea { width: 100%; padding: 10px; margin-bottom: 12px; border: 1px solid #ccc; border-radius: 6px; }
        .message { padding: 12px; border-radius: 6px; margin-bottom: 16px; }
        .message-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn-sm { padding: 8px 12px; font-size: 13px; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="account-container">
        <div class="account-header">
            <h1>My Account</h1>
            <p>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
        </div>
        
        <div class="account-tabs">
            <div class="account-tab active" data-tab="profile">Profile</div>
            <div class="account-tab" data-tab="orders">Order History</div>
            <div class="account-tab" data-tab="addresses">Addresses</div>
            <div class="account-tab" data-tab="password">Change Password</div>
        </div>
        
        <!-- Profile Tab -->
        <div class="account-content active" id="profile">
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <div id="profileMessage"></div>
                <form id="profileForm">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    
                    <button type="submit" class="btn">Update Profile</button>
                </form>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> Account Details</h3>
                <div class="info-row">
                    <span class="info-label">Account Type:</span>
                    <span class="info-value"><?php echo ucfirst($user['role']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value"><?php echo ucfirst($user['status']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Member Since:</span>
                    <span class="info-value"><?php echo formatDate($user['created_at'], 'long'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Login:</span>
                    <span class="info-value"><?php echo $user['last_login'] ? formatDateTime($user['last_login'], 'long') : 'Never'; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Orders Tab -->
        <div class="account-content" id="orders">
            <div class="info-card">
                <h3><i class="fas fa-shopping-bag"></i> Order History</h3>
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <p>You haven't placed any orders yet.</p>
                        <a href="shop.php" class="btn">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td><?php echo formatDate($order['created_at'], 'long'); ?></td>
                                <td><?php echo $order['item_count']; ?> items</td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td><a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Addresses Tab -->
        <div class="account-content" id="addresses">
            <div class="info-card">
                <h3><i class="fas fa-map-marker-alt"></i> Saved Addresses</h3>
                <div id="addressMessage"></div>
                <div id="addresses-list">
                    <?php if (empty($addresses)): ?>
                        <div class="empty-state">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>No saved addresses yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($addresses as $address): ?>
                        <div class="address-card <?php echo $address['is_default'] ? 'default' : ''; ?>" data-id="<?php echo $address['id']; ?>">
                            <?php if ($address['is_default']): ?>
                                <span class="default-badge">Default</span>
                            <?php endif; ?>
                            <strong><?php echo htmlspecialchars($address['street_address']); ?></strong><br>
                            <?php echo htmlspecialchars($address['city']); ?> <?php echo htmlspecialchars($address['postcode']); ?><br>
                            <div style="margin-top: 10px; display: flex; gap: 10px;">
                                <?php if (!$address['is_default']): ?>
                                    <button class="btn btn-sm btn-set-default" data-address-id="<?php echo $address['id']; ?>">
                                        <i class="fas fa-star"></i> Set Default
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-edit-address" data-address-id="<?php echo $address['id']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete-address" data-address-id="<?php echo $address['id']; ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (count($addresses) < 5): ?>
                    <button class="btn" onclick="openAddAddressModal()" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i> Add New Address
                    </button>
                <?php else: ?>
                    <p style="color: #666; margin-top: 10px;"><i class="fas fa-info-circle"></i> Maximum 5 addresses allowed</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Password Tab -->
        <div class="account-content" id="password">
            <div class="info-card">
                <h3><i class="fas fa-lock"></i> Change Password</h3>
                <div id="passwordMessage"></div>
                <form id="passwordForm">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                    
                    <label>New Password (min 8 characters)</label>
                    <input type="password" name="new_password" minlength="8" required>
                    
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                    
                    <button type="submit" class="btn">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
    <?php include 'components/toast-notification.php'; ?>
    
    <script>
        // Tab switching
        document.querySelectorAll('.account-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                
                document.querySelectorAll('.account-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.account-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(tabName).classList.add('active');
            });
        });
        
        // Update profile
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const messageDiv = document.getElementById('profileMessage');
            messageDiv.innerHTML = '';
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const res = await fetch('/demolitiontraders/backend/api/user/update-profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                
                if (result.success) {
                    messageDiv.innerHTML = '<div class="message message-success">Profile updated successfully!</div>';
                } else {
                    messageDiv.innerHTML = '<div class="message message-error">' + result.message + '</div>';
                }
            } catch (err) {
                messageDiv.innerHTML = '<div class="message message-error">Server error. Please try again.</div>';
            }
        });
        
        // Change password
        document.getElementById('passwordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const messageDiv = document.getElementById('passwordMessage');
            messageDiv.innerHTML = '';
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            if (data.new_password !== data.confirm_password) {
                messageDiv.innerHTML = '<div class="message message-error">New passwords do not match!</div>';
                return;
            }
            
            try {
                const res = await fetch('/demolitiontraders/backend/api/user/change-password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                
                if (result.success) {
                    messageDiv.innerHTML = '<div class="message message-success">Password changed successfully!</div>';
                    this.reset();
                } else {
                    messageDiv.innerHTML = '<div class="message message-error">' + result.message + '</div>';
                }
            } catch (err) {
                messageDiv.innerHTML = '<div class="message message-error">Server error. Please try again.</div>';
            }
        });
        
        // Address Management Functions
        function openAddAddressModal() {
            console.log('Opening add address modal');
            createAddressModal();
        }
        
        function createAddressModal(address = null) {
            console.log('Creating address modal', address);
            const modal = document.createElement('div');
            modal.id = 'address-modal';
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 10000;';
            
            const isEdit = address !== null;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 12px; padding: 30px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
                    <h2 style="margin: 0 0 20px 0;">${isEdit ? 'Edit' : 'Add'} Address</h2>
                    <form id="address-form" onsubmit="return false;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Address *</label>
                            <input type="text" name="address" value="${address?.address || ''}" required placeholder="e.g., 123 Main Street" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" onkeydown="if(event.key==='Enter') event.preventDefault();">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">City *</label>
                                <input type="text" name="city" value="${address?.city || ''}" required placeholder="e.g., Hamilton" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" onkeydown="if(event.key==='Enter') event.preventDefault();">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Postcode *</label>
                                <input type="text" name="postcode" value="${address?.postcode || ''}" required placeholder="e.g., 3216" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" onkeydown="if(event.key==='Enter') event.preventDefault();">
                            </div>
                        </div>
                        
                        ${!isEdit || !address.is_default ? `
                        <div style="margin-top: 15px;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="is_default" value="1" ${address?.is_default ? 'checked' : ''} style="width: auto; margin: 0;">
                                <span>Set as default address</span>
                            </label>
                        </div>
                        ` : ''}
                        
                        <div style="display: flex; gap: 10px; margin-top: 25px;">
                            <button type="button" class="btn btn-submit-address" style="flex: 1; padding: 12px;">
                                ${isEdit ? 'Update' : 'Add'} Address
                            </button>
                            <button type="button" class="btn btn-cancel-modal" style="flex: 1; padding: 12px; background: #6c757d;">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            `;
            
            // Add event listeners after modal is in DOM
            setTimeout(() => {
                const form = document.getElementById('address-form');
                const cancelBtn = modal.querySelector('.btn-cancel-modal');
                const submitBtn = modal.querySelector('.btn-submit-address');
                
                console.log('Form found:', form);
                console.log('Cancel button found:', cancelBtn);
                console.log('Submit button found:', submitBtn);
                
                // Cancel button handler
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        console.log('Cancel clicked');
                        closeAddressModal();
                    });
                }
                
                // Submit button handler (not form submit!)
                if (submitBtn && form) {
                    submitBtn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        console.log('Submit button clicked!');
                        
                        // Validate form
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            return;
                        }
                        
                        const formData = new FormData(form);
                        const data = Object.fromEntries(formData);
                        
                        console.log('Submitting address:', data);
                        
                        if (isEdit) {
                            data.id = address.id;
                        }
                        
                        try {
                            console.log('Making request to:', '/demolitiontraders/backend/api/user/manage-address.php');
                            console.log('Method:', isEdit ? 'PUT' : 'POST');
                            console.log('Data:', JSON.stringify(data));
                            
                            const res = await fetch('/demolitiontraders/backend/api/user/manage-address.php', {
                                method: isEdit ? 'PUT' : 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(data)
                            });
                            
                            console.log('Response status:', res.status);
                            const result = await res.json();
                            console.log('Response:', result);
                            
                            if (result.success) {
                                alert('Address added successfully!');
                                closeAddressModal();
                                location.reload(); // Reload to show updated addresses
                            } else {
                                alert('Error: ' + result.message);
                            }
                        } catch (err) {
                            console.error('Error:', err);
                            alert('Server error: ' + err.message);
                        }
                    });
                }
            }, 100);
            
            // Append modal to DOM AFTER setting up the setTimeout
            document.body.appendChild(modal);
        }
        
        function closeAddressModal() {
            const modal = document.getElementById('address-modal');
            if (modal) modal.remove();
        }
        
        async function editAddress(id) {
            try {
                const res = await fetch('/demolitiontraders/backend/api/user/addresses.php');
                const result = await res.json();
                
                if (result.success) {
                    const address = result.addresses.find(a => a.id == id);
                    if (address) {
                        // Map street_address to address for the form
                        address.address = address.street_address;
                        createAddressModal(address);
                    }
                }
            } catch (err) {
                alert('Failed to load address details');
            }
        }
        
        async function deleteAddress(id) {
            console.log('deleteAddress called with id:', id);
            
            // Create custom confirm modal
            const confirmModal = document.createElement('div');
            confirmModal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 99999;';
            
            confirmModal.innerHTML = `
                <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; width: 90%; text-align: center;">
                    <h3 style="margin: 0 0 15px 0; color: #dc3545;">Delete Address?</h3>
                    <p style="margin: 0 0 25px 0; color: #666;">Are you sure you want to delete this address? This action cannot be undone.</p>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button id="confirm-delete-yes" class="btn" style="padding: 12px 24px; background: #dc3545; color: white;">
                            Yes, Delete
                        </button>
                        <button id="confirm-delete-no" class="btn" style="padding: 12px 24px; background: #6c757d; color: white;">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(confirmModal);
            
            // Handle Yes button
            document.getElementById('confirm-delete-yes').addEventListener('click', async () => {
                console.log('User clicked YES to delete');
                confirmModal.remove();
                
                try {
                    const res = await fetch('/demolitiontraders/backend/api/user/manage-address.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    });
                    const result = await res.json();
                    
                    if (result.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (err) {
                    alert('Server error. Please try again.');
                }
            });
            
            // Handle No button
            document.getElementById('confirm-delete-no').addEventListener('click', () => {
                console.log('User clicked CANCEL delete');
                confirmModal.remove();
            });
        }
        
        async function setDefaultAddress(id) {
            try {
                const res = await fetch('/demolitiontraders/backend/api/user/manage-address.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, is_default: 1 })
                });
                const result = await res.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (err) {
                alert('Server error. Please try again.');
            }
        }
        
        // Event delegation for address buttons
        document.addEventListener('click', function(e) {
            // Set default button
            if (e.target.closest('.btn-set-default')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Set default clicked');
                const btn = e.target.closest('.btn-set-default');
                const addressId = btn.getAttribute('data-address-id');
                setDefaultAddress(addressId);
                return;
            }
            
            // Edit button
            if (e.target.closest('.btn-edit-address')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Edit clicked');
                const btn = e.target.closest('.btn-edit-address');
                const addressId = btn.getAttribute('data-address-id');
                editAddress(addressId);
                return;
            }
            
            // Delete button
            if (e.target.closest('.btn-delete-address')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Delete button clicked');
                const btn = e.target.closest('.btn-delete-address');
                const addressId = btn.getAttribute('data-address-id');
                console.log('Address ID to delete:', addressId);
                deleteAddress(addressId);
                return;
            }
        });
    </script>
</body>
</html>
