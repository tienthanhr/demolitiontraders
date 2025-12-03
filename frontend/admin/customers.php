<?php
require_once '../config.php';
require_once '../components/date-helper.php';

ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    header('Location: ' . BASE_PATH . 'admin-login');
    exit;
}

require_once '../../backend/config/database.php';
$db = Database::getInstance();

// Get customer statistics
$stats = [
    'total_customers' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")['count'] ?? 0,
    'active_customers' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND status = 'active'")['count'] ?? 0,
    'total_orders' => $db->fetchOne(
        "SELECT COUNT(*) as count FROM orders o 
         INNER JOIN users u ON o.user_id = u.id 
         WHERE u.role = 'customer'"
    )['count'] ?? 0,
    'with_orders' => $db->fetchOne("SELECT COUNT(DISTINCT user_id) as count FROM orders WHERE user_id IS NOT NULL")['count'] ?? 0,
];

// Get all customers with order counts
$customers = $db->fetchAll(
    "SELECT u.*, 
     COUNT(DISTINCT o.id) as order_count,
     COALESCE(SUM(o.total_amount), 0) as total_spent
     FROM users u
     LEFT JOIN orders o ON u.id = o.user_id
     WHERE u.role = 'customer'
     GROUP BY u.id
     ORDER BY u.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers Management - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="admin/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/api-helper.js"></script>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-card i { font-size: 32px; color: #2f3192; margin-bottom: 10px; }
        .stat-card h3 { font-size: 28px; margin: 8px 0; color: #2f3192; }
        .stat-card p { color: #666; margin: 0; font-size: 14px; }
        .search-box { margin-bottom: 20px; }
        .search-box input { width: 100%; max-width: 400px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; }
        .table-container { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #2f3192; }
        tr:hover { background: #f8f9fa; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-active { background: #28a745; color: white; }
        .badge-inactive { background: #ffc107; color: #333; }
        .badge-suspended { background: #dc3545; color: white; }
        .btn { padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .btn-sm { padding: 4px 8px; font-size: 11px; }
        .btn-primary { background: #2f3192; color: white; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-light { background: white; color: #333; border: 1px solid #ddd; }
        .btn-secondary { background: #6c757d; color: white; }
        
        /* Bulk Actions Bar */
        .bulk-actions { 
            display: flex; 
            gap: 15px; 
            align-items: center; 
            margin-bottom: 15px; 
            padding: 20px; 
            background: linear-gradient(135deg, #2f3192 0%, #4a4eb8 100%);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(47, 49, 146, 0.3);
            animation: slideDown 0.3s ease-out;
        }
        .bulk-actions .selected-count { 
            font-weight: 600; 
            color: white;
            font-size: 14px;
        }
        .bulk-actions .selected-count i {
            margin-right: 8px;
        }
        .bulk-actions select {
            padding: 8px 12px;
            border: 2px solid white;
            border-radius: 8px;
            background: white;
            color: #2f3192;
            font-weight: 600;
            cursor: pointer;
            min-width: 180px;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Undo Bar */
        #undo-bar {
            display: none;
            margin-bottom: 15px;
            padding: 20px;
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
            animation: slideDown 0.3s ease-out;
        }
        #undo-bar .btn-warning {
            background: white;
            color: #f39c12;
            font-weight: 600;
        }
        #undo-bar .btn-warning:hover {
            background: #fff3cd;
        }
        #undo-bar .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid white;
        }
        #undo-bar .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }
        
        th.sortable { cursor: pointer; user-select: none; }
        th.sortable:hover { background: #e9ecef; }
        th.sortable i { margin-left: 5px; font-size: 10px; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow-y: auto; }
        .modal.show { display: block; }
        .modal-dialog { margin: 50px auto; max-width: 600px; }
        .modal-content { background: white; border-radius: 10px; box-shadow: 0 5px 30px rgba(0,0,0,0.3); }
        .modal-header { padding: 20px 30px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; color: #2f3192; }
        .modal-close { background: none; border: none; font-size: 28px; cursor: pointer; color: #999; line-height: 1; padding: 0; width: 30px; height: 30px; }
        .modal-close:hover { color: #333; }
        .modal-body { padding: 30px; }
        .modal-footer { padding: 20px 30px; border-top: 1px solid #eee; display: flex; gap: 10px; justify-content: flex-end; }
        .info-group { margin-bottom: 20px; }
        .info-group label { display: block; font-weight: 600; color: #666; margin-bottom: 5px; font-size: 13px; text-transform: uppercase; }
        .info-group .value { font-size: 16px; color: #333; padding: 8px 0; }
        .info-group input, .info-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .info-group select { cursor: pointer; }
    </style>
    <script src="../assets/js/api-helper.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <?php include 'topbar.php'; ?>

<!-- Customers Management Content -->
<div class="content-section">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-users"></i> Customers Management</h2>
        <button class="btn btn-primary" onclick="location.reload()">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <h3><?php echo $stats['total_customers']; ?></h3>
            <p>Total Customers</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-check-circle"></i>
            <h3><?php echo $stats['active_customers']; ?></h3>
            <p>Active Customers</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-shopping-bag"></i>
            <h3><?php echo $stats['total_orders']; ?></h3>
            <p>Total Orders</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-user-check"></i>
            <h3><?php echo $stats['with_orders']; ?></h3>
            <p>Customers with Orders</p>
        </div>
    </div>

    <!-- Info Notice -->
    <div style="background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px 20px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-info-circle" style="color: #0c5460; font-size: 20px;"></i>
        <div style="flex: 1;">
            <strong style="color: #0c5460; font-size: 15px;">ℹ️ Undo Available:</strong>
            <p style="margin: 5px 0 0 0; color: #0c5460; font-size: 14px;">Deleted customers can be <strong>restored within 10 seconds</strong> using the Undo button.</p>
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="search-customers" placeholder="Search customers by name, email..." onkeyup="searchCustomers()">
    </div>

    <div class="bulk-actions" id="bulkActions" style="display: none;">
        <span class="selected-count">
            <i class="fas fa-check-circle"></i> <span id="selectedCount">0</span> item<span id="selectedPlural">s</span> selected
        </span>
        <select id="bulk-action">
            <option value="">Select Action</option>
            <option value="activate">Activate</option>
            <option value="suspend">Suspend</option>
            <option value="delete">Delete Selected</option>
        </select>
        <button class="btn btn-light btn-sm" onclick="applyBulkAction()">
            <i class="fas fa-bolt"></i> Apply
        </button>
        <button class="btn btn-secondary btn-sm" onclick="clearSelection()">
            <i class="fas fa-times"></i> Clear
        </button>
    </div>

    <!-- Undo Bar (below bulk actions) -->
    <div id="undo-bar" style="display: none;">
        <div style="display: flex; align-items: center; gap: 15px; justify-content: space-between;">
            <span id="undo-message"></span>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-warning btn-sm" onclick="undoLastAction()">
                    <i class="fas fa-undo"></i> Undo
                </button>
                <button class="btn btn-secondary btn-sm" onclick="dismissUndo()">
                    <i class="fas fa-times"></i> Dismiss
                </button>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table id="customers-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllHeader" onchange="toggleSelectAll(this)"></th>
                    <th class="sortable" onclick="sortTable('id')">
                        ID <i class="fas fa-sort" id="sort-id"></i>
                    </th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="customers-tbody">
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 40px;">
                        <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 16px;"></i>
                        <p>No customers found.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                    <tr data-customer-id="<?php echo $customer['id']; ?>">
                        <td data-label="Select"><input type="checkbox" class="customer-checkbox" value="<?php echo $customer['id']; ?>" onchange="updateBulkActions()"></td>
                        <td data-label="ID"><?php echo $customer['id']; ?></td>
                        <td data-label="Name"><strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong></td>
                        <td data-label="Email"><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td data-label="Phone"><?php echo htmlspecialchars($customer['phone'] ?? '-'); ?></td>
                        <td data-label="Status">
                            <span class="badge badge-<?php echo $customer['status']; ?>">
                                <?php echo ucfirst($customer['status']); ?>
                            </span>
                        </td>
                        <td data-label="Registered"><?php echo formatDate($customer['created_at'], 'long'); ?></td>
                        <td data-label="Orders"><strong><?php echo $customer['order_count']; ?></strong></td>
                        <td data-label="Total Spent"><strong>$<?php echo number_format($customer['total_spent'], 2); ?></strong></td>
                        <td data-label="Actions">
                            <button class="btn btn-sm btn-primary" onclick="viewCustomer(<?php echo $customer['id']; ?>)" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick="resetPassword(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>')" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                            <?php if ($customer['status'] === 'active'): ?>
                            <button class="btn btn-sm btn-warning" onclick="suspendCustomer(<?php echo $customer['id']; ?>)" title="Suspend Account">
                                <i class="fas fa-ban"></i>
                            </button>
                            <?php elseif ($customer['status'] === 'suspended'): ?>
                            <button class="btn btn-sm btn-success" onclick="activateCustomer(<?php echo $customer['id']; ?>)" title="Activate Account">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-danger" onclick="deleteCustomer(<?php echo $customer['id']; ?>)" title="Delete Account">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Customer Detail Modal -->
<div id="customerModal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user"></i> Customer Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="info-group">
                    <label>Customer ID</label>
                    <div class="value" id="modal-id"></div>
                </div>
                <div class="info-group">
                    <label>Full Name</label>
                    <div class="value" id="modal-name"></div>
                </div>
                <div class="info-group">
                    <label>Email</label>
                    <div class="value" id="modal-email"></div>
                </div>
                <div class="info-group">
                    <label>Phone</label>
                    <div class="value" id="modal-phone"></div>
                </div>
                <div class="info-group">
                    <label>Role</label>
                    <select id="modal-role" onchange="roleChanged()">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="info-group">
                    <label>Status</label>
                    <div class="value">
                        <span class="badge" id="modal-status-badge"></span>
                    </div>
                </div>
                <div class="info-group">
                    <label>Member Since</label>
                    <div class="value" id="modal-created"></div>
                </div>
                <div class="info-group">
                    <label>Last Login</label>
                    <div class="value" id="modal-lastlogin"></div>
                </div>
                <div class="info-group">
                    <label>Total Orders</label>
                    <div class="value" id="modal-orders"></div>
                </div>
                <div class="info-group">
                    <label>Total Spent</label>
                    <div class="value" id="modal-spent"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Close</button>
                <button class="btn btn-primary" id="saveRoleBtn" onclick="saveRole()" style="display:none;">
                    <i class="fas fa-save"></i> Save Role
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Custom Confirm Modal -->
<div id="custom-confirm-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <h3 style="margin: 0 0 15px 0; color: #2f3192; font-size: 20px;">
            <i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i> Confirm Action
        </h3>
        <p id="confirm-message" style="margin: 0 0 25px 0; color: #666; font-size: 15px; line-height: 1.6;"></p>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn btn-secondary" onclick="resolveConfirm(false)" style="padding: 10px 20px;">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="btn btn-danger" onclick="resolveConfirm(true)" style="padding: 10px 20px;">
                <i class="fas fa-check"></i> Confirm
            </button>
        </div>
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

        </main>
    </div>
</body>
</html>

<script>
let currentCustomer = null;
let originalRole = null;
let sortDirection = { id: 'desc' }; // Default sort by ID descending
let lastBulkAction = null; // Store last action for undo
let resetPasswordUserId = null;
let resetPasswordUserName = null;
let confirmResolve = null; // For custom confirm modal

// Custom confirm function
function customConfirm(message) {
    return new Promise((resolve) => {
        confirmResolve = resolve;
        document.getElementById('confirm-message').textContent = message;
        document.getElementById('custom-confirm-modal').style.display = 'flex';
    });
}

function resolveConfirm(value) {
    document.getElementById('custom-confirm-modal').style.display = 'none';
    if (confirmResolve) {
        confirmResolve(value);
        confirmResolve = null;
    }
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
    const count = checkboxes.length;
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const selectedPlural = document.getElementById('selectedPlural');
    const selectAllHeader = document.getElementById('selectAllHeader');
    
    selectedCount.textContent = count;
    selectedPlural.textContent = count === 1 ? '' : 's';
    bulkActions.style.display = count > 0 ? 'flex' : 'none';
    
    // Update header checkbox state
    const allCheckboxes = document.querySelectorAll('.customer-checkbox');
    selectAllHeader.checked = count > 0 && count === allCheckboxes.length;
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('selectAllHeader').checked = false;
    document.getElementById('bulk-action').value = '';
    updateBulkActions();
}

async function applyBulkAction() {
    const action = document.getElementById('bulk-action').value;
    
    // Store all logs in array
    const debugLogs = [];
    const log = (msg, data = null) => {
        const timestamp = new Date().toISOString();
        const logEntry = data ? `[${timestamp}] ${msg}: ${JSON.stringify(data)}` : `[${timestamp}] ${msg}`;
        debugLogs.push(logEntry);
        console.log(msg, data || '');
    };
    
    log('=== BULK ACTION START ===');
    log('Action selected', action);
    
    if (!action) {
        alert('Please select an action');
        return;
    }
    
    const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
    const customerIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    log('Selected customer IDs', customerIds);
    
    if (customerIds.length === 0) {
        alert('Please select at least one customer');
        return;
    }
    
    const actionText = action === 'delete' ? 'delete' : action === 'activate' ? 'activate' : 'suspend';
    log('Action text', actionText);
    
    // Use custom confirm
    const confirmed = await customConfirm(`Are you sure you want to ${actionText} ${customerIds.length} customer(s)?`);
    if (!confirmed) {
        log('User cancelled action');
        return;
    }
    
    // Store original states for undo (including full customer data for delete)
    const originalStates = {};
    customerIds.forEach(customerId => {
        const row = document.querySelector(`tr[data-customer-id="${customerId}"]`);
        if (row) {
            const cells = row.cells;
            const status = cells[5].querySelector('.badge').textContent.trim().toLowerCase();
            
            originalStates[customerId] = {
                id: customerId,
                status: status,
                fullData: action === 'delete' ? {
                    // Extract customer data from row
                    first_name: cells[2].querySelector('strong').textContent.split(' ')[0],
                    last_name: cells[2].querySelector('strong').textContent.split(' ').slice(1).join(' '),
                    email: cells[3].textContent.trim(),
                    phone: cells[4].textContent.trim(),
                    created_at: cells[6].textContent.trim(),
                    // Also store HTML for quick restore
                    html: row.outerHTML,
                    position: Array.from(row.parentNode.children).indexOf(row)
                } : null
            };
        }
    });
    log('Original states saved', originalStates);
    
    // Process each customer
    let successCount = 0;
    let failCount = 0;
    
    for (let i = 0; i < customerIds.length; i++) {
        const customerId = customerIds[i];
        try {
            log(`\n>>> Processing customer ${i+1}/${customerIds.length}: ID=${customerId}`);
            
            if (action === 'delete') {
                const url = `${getApiUrl('/api/admin/delete-user.php')}`;
                const payload = { user_id: customerId };
                log('DELETE URL', url);
                log('DELETE Payload', payload);
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                log('DELETE Response status', response.status);
                const responseText = await response.text();
                log('DELETE Response body', responseText);
                
                try {
                    const result = JSON.parse(responseText);
                    if (result.success) {
                        successCount++;
                        log('✓ SUCCESS - Customer deleted');
                    } else {
                        failCount++;
                        log('✗ FAILED - ' + (result.message || 'Delete failed'));
                    }
                } catch (e) {
                    failCount++;
                    log('✗ FAILED - Invalid JSON response');
                }
            } else {
                // Activate or suspend
                const status = action === 'activate' ? 'active' : 'suspended';
                const url = `${getApiUrl('/api/admin/update-user-status.php')}`;
                const payload = { user_id: customerId, status: status };
                
                log('POST URL', url);
                log('POST Payload', payload);
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                log('POST Response status', response.status);
                const responseText = await response.text();
                log('POST Response body', responseText);
                
                try {
                    const result = JSON.parse(responseText);
                    if (result.success) {
                        successCount++;
                        log(`✓ SUCCESS - Customer ${actionText}d`);
                    } else {
                        failCount++;
                        log('✗ FAILED - ' + (result.message || `${actionText} failed`));
                    }
                } catch (e) {
                    failCount++;
                    log('✗ FAILED - Invalid JSON response');
                }
            }
            
            log(`--- Customer ${i+1} complete. Success: ${successCount}, Failed: ${failCount} ---`);
            
        } catch (error) {
            log('ERROR processing customer', {customerId, error: error.message});
            console.error(`Error processing customer ${customerId}:`, error);
            failCount++;
        }
    }
    
    log('=== BULK ACTION COMPLETE ===');
    log('Success count', successCount);
    log('Fail count', failCount);
    
    // Print all logs in one block
    console.log('\n\n========== COMPLETE DEBUG LOG ==========');
    debugLogs.forEach(logLine => console.log(logLine));
    console.log('========================================\n\n');
    
    if (failCount > 0) {
        alert(`Completed: ${successCount} successful, ${failCount} failed\n\nCheck console for detailed logs.`);
        clearSelection();
    } else {
        // Store action for undo
        lastBulkAction = {
            action: action,
            customers: originalStates,
            count: successCount
        };
        
        // Update UI in real-time
        updateUIAfterBulkAction(action, customerIds);
        
        showUndoBar(action, successCount);
        clearSelection();
    }
}

// Update UI in real-time after bulk action
function updateUIAfterBulkAction(action, customerIds) {
    customerIds.forEach(customerId => {
        const row = document.querySelector(`tr[data-customer-id="${customerId}"]`);
        if (!row) return;
        
        if (action === 'delete') {
            // Remove row with animation
            row.style.transition = 'opacity 0.3s, transform 0.3s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-20px)';
            setTimeout(() => row.remove(), 300);
        } else {
            // Update status badge
            const statusCell = row.cells[5]; // Status column
            const statusBadge = statusCell.querySelector('.badge');
            
            if (action === 'activate') {
                statusBadge.className = 'badge badge-active';
                statusBadge.textContent = 'Active';
            } else if (action === 'suspend') {
                statusBadge.className = 'badge badge-suspended';
                statusBadge.textContent = 'Suspended';
            }
            
            // Highlight row briefly
            row.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                row.style.transition = 'background-color 0.5s';
                row.style.backgroundColor = '';
            }, 500);
        }
    });
}

// Undo functionality
let undoTimeout = null;

function showUndoBar(action, count) {
    const undoBar = document.getElementById('undo-bar');
    const undoMessage = document.getElementById('undo-message');
    
    const actionText = action === 'delete' ? 'deleted' : action === 'activate' ? 'activated' : 'suspended';
    undoMessage.innerHTML = `<i class="fas fa-info-circle"></i> ${count} customer(s) ${actionText}`;
    
    undoBar.style.display = 'flex';
    
    // Auto-hide after 10 seconds
    undoTimeout = setTimeout(() => {
        dismissUndo(false); // Just dismiss, no reload needed
    }, 10000);
}

function dismissUndo(shouldReload = false) {
    const undoBar = document.getElementById('undo-bar');
    undoBar.style.display = 'none';
    lastBulkAction = null;
    
    // Clear timeout if it exists
    if (undoTimeout) {
        clearTimeout(undoTimeout);
        undoTimeout = null;
    }
}

async function undoLastAction() {
    if (!lastBulkAction) return;
    
    const { action, customers, count } = lastBulkAction;
    
    // Hide undo bar but don't reload yet
    dismissUndo(false);
    
    console.log('Undoing action:', action);
    console.log('Customers to restore:', customers);
    
    // Perform undo
    let successCount = 0;
    let failCount = 0;
    
    for (const customer of customers) {
        try {
            if (action === 'delete') {
                // Restore deleted customer via API
                if (customer.fullData) {
                    const url = `${getApiUrl('/api/admin/restore-user.php')}`;
                    const payload = {
                        original_id: parseInt(customer.id),
                        email: customer.fullData.email,
                        first_name: customer.fullData.first_name,
                        last_name: customer.fullData.last_name,
                        phone: customer.fullData.phone,
                        status: customer.status,
                        role: 'customer'
                    };
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        successCount++;
                        console.log('✓ Customer restored successfully:', customer.id);
                    } else {
                        failCount++;
                        console.error(`Failed to restore customer ${customer.id}: ${result.message}`);
                    }
                } else {
                    failCount++;
                    console.error(`No data to restore for customer ${customer.id}`);
                }
            } else {
                // Restore original status
                const url = `${getApiUrl('/api/admin/update-user-status.php')}`;
                const payload = { user_id: parseInt(customer.id), status: customer.status };
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const responseText = await response.text();
                const result = JSON.parse(responseText);
                
                if (result.success) {
                    successCount++;
                } else {
                    failCount++;
                }
            }
        } catch (error) {
            console.error(`Error undoing action for customer ${customer.id}:`, error);
            failCount++;
        }
    }
    
    if (failCount > 0) {
        alert(`Undo completed: ${successCount} successful, ${failCount} failed`);
    } else {
        // Reload page to show restored customers
        if (action === 'delete') {
            showSuccess(`Successfully restored ${count} customer(s)!\n\n⚠️ IMPORTANT: Restored customers have a temporary password.\nPlease use the "Reset Password" button to set a new password for them.`);
        } else {
            showSuccess(`Successfully undid ${count} customer(s)!`);
        }
        setTimeout(() => location.reload(), 1500);
    }
    
    lastBulkAction = null;
}

async function bulkDeleteCustomers() {
    const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
    const customerIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (customerIds.length === 0) {
        showWarning('Please select customers to delete');
        return;
    }
    
    const confirmed = await showConfirm(
        `⚠️ DANGER: Are you sure you want to DELETE ${customerIds.length} customer(s)?\n\n❌ This action CANNOT be undone!\n❌ All customer data, orders, and history will be permanently removed!`,
        'Bulk Delete Customers',
        true
    );
    if (!confirmed) return;
    
    const confirmText = prompt('Type "DELETE" (in capital letters) to confirm bulk deletion:');
    if (confirmText !== 'DELETE') {
        showWarning('Deletion cancelled. You must type "DELETE" to confirm.');
        return;
    }
    
    try {
        let successCount = 0;
        let failCount = 0;
        
        for (const customerId of customerIds) {
            const res = await fetch(getApiUrl('/api/admin/delete-user.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: customerId })
            });
            
            const result = await res.json();
            if (result.success) {
                successCount++;
            } else {
                failCount++;
            }
        }
        
        alert(`✓ Bulk delete complete:\n${successCount} deleted successfully\n${failCount > 0 ? failCount + ' failed' : ''}`);
        location.reload();
        
    } catch (err) {
        alert('✗ Server error. Please try again.');
        console.error(err);
    }
}

function sortTable(column) {
    const tbody = document.getElementById('customers-tbody');
    const rows = Array.from(tbody.getElementsByTagName('tr'));
    
    // Toggle sort direction
    if (!sortDirection[column]) sortDirection[column] = 'asc';
    sortDirection[column] = sortDirection[column] === 'asc' ? 'desc' : 'asc';
    
    // Update sort icons
    document.querySelectorAll('th.sortable i').forEach(icon => {
        icon.className = 'fas fa-sort';
    });
    const icon = document.getElementById(`sort-${column}`);
    icon.className = sortDirection[column] === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
    
    // Sort rows
    rows.sort((a, b) => {
        let aVal, bVal;
        
        if (column === 'id') {
            aVal = parseInt(a.getAttribute('data-customer-id'));
            bVal = parseInt(b.getAttribute('data-customer-id'));
        }
        
        if (sortDirection[column] === 'asc') {
            return aVal - bVal;
        } else {
            return bVal - aVal;
        }
    });
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}

function searchCustomers() {
    const input = document.getElementById('search-customers');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('customers-table');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length - 1; j++) {
            if (cells[j].textContent.toLowerCase().includes(filter)) {
                found = true;
                break;
            }
        }
        
        rows[i].style.display = found ? '' : 'none';
    }
}

function viewCustomer(customerId) {
    // Find customer data from table
    const table = document.getElementById('customers-table');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        // cells[0] is checkbox, cells[1] is ID
        if (cells[1] && cells[1].textContent == customerId) {
            // Extract data from row
            currentCustomer = {
                id: customerId,
                name: cells[2].textContent.trim(),
                email: cells[3].textContent.trim(),
                phone: cells[4].textContent.trim(),
                status: cells[5].textContent.trim().toLowerCase(),
                created: cells[6].textContent.trim(),
                orders: cells[7].textContent.trim(),
                spent: cells[8].textContent.trim()
            };
            
            // Populate modal
            document.getElementById('modal-id').textContent = currentCustomer.id;
            document.getElementById('modal-name').textContent = currentCustomer.name;
            document.getElementById('modal-email').textContent = currentCustomer.email;
            document.getElementById('modal-phone').textContent = currentCustomer.phone || 'N/A';
            document.getElementById('modal-status-badge').textContent = currentCustomer.status.charAt(0).toUpperCase() + currentCustomer.status.slice(1);
            document.getElementById('modal-status-badge').className = 'badge badge-' + currentCustomer.status;
            document.getElementById('modal-created').textContent = currentCustomer.created;
            document.getElementById('modal-lastlogin').textContent = 'N/A';
            document.getElementById('modal-orders').textContent = currentCustomer.orders + ' orders';
            document.getElementById('modal-spent').textContent = currentCustomer.spent;
            
            // Set role (default to customer since we don't have it in table)
            document.getElementById('modal-role').value = 'customer';
            originalRole = 'customer';
            document.getElementById('saveRoleBtn').style.display = 'none';
            
            // Show modal
            document.getElementById('customerModal').classList.add('show');
            break;
        }
    }
}

function closeModal() {
    document.getElementById('customerModal').classList.remove('show');
    currentCustomer = null;
    originalRole = null;
}

function roleChanged() {
    const newRole = document.getElementById('modal-role').value;
    const saveBtn = document.getElementById('saveRoleBtn');
    
    if (newRole !== originalRole) {
        saveBtn.style.display = 'inline-block';
    } else {
        saveBtn.style.display = 'none';
    }
}

async function saveRole() {
    const newRole = document.getElementById('modal-role').value;
    
    if (!confirm(`Change customer role to "${newRole.toUpperCase()}"?`)) return;
    
    try {
        const res = await fetch(getApiUrl('/api/admin/update-user-role.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                user_id: currentCustomer.id, 
                role: newRole 
            })
        });
        
        const result = await res.json();
        
        if (result.success) {
            alert('✓ Role updated successfully!');
            originalRole = newRole;
            document.getElementById('saveRoleBtn').style.display = 'none';
            
            // If changed to non-customer, they won't appear in this list after reload
            if (newRole !== 'customer') {
                alert('Note: This user will no longer appear in the Customers list after reload since they are now an ADMIN.');
            }
        } else {
            alert('✗ Error: ' + result.message);
        }
    } catch (err) {
        alert('✗ Server error. Please try again.');
        console.error(err);
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('customerModal');
    if (event.target === modal) {
        closeModal();
    }
}

async function suspendCustomer(customerId) {
    if (!confirm('⚠️ Are you sure you want to SUSPEND this customer?\n\nThey will not be able to login until you activate their account again.')) return;
    
    try {
        const res = await fetch(getApiUrl('/api/admin/update-user-status.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: customerId, status: 'suspended' })
        });
        
        const result = await res.json();
        
        if (result.success) {
            alert('✓ Customer account suspended successfully!');
            location.reload();
        } else {
            alert('✗ Error: ' + result.message);
        }
    } catch (err) {
        alert('✗ Server error. Please try again.');
        console.error(err);
    }
}

async function activateCustomer(customerId) {
    if (!confirm('Are you sure you want to ACTIVATE this customer account?')) return;
    
    try {
        const res = await fetch(getApiUrl('/api/admin/update-user-status.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: customerId, status: 'active' })
        });
        
        const result = await res.json();
        
        if (result.success) {
            alert('✓ Customer account activated successfully!');
            location.reload();
        } else {
            alert('✗ Error: ' + result.message);
        }
    } catch (err) {
        alert('✗ Server error. Please try again.');
        console.error(err);
    }
}

async function deleteCustomer(customerId) {
    const confirmed = await showConfirm(
        '⚠️ Are you sure you want to DELETE this customer account?\n\nYou can undo this within 10 seconds.',
        'Delete Customer',
        true
    );
    if (!confirmed) return;
    
    try {
        // Get customer data before deletion for undo
        const customerRow = document.querySelector(`tr[data-customer-id="${customerId}"]`);
        const email = customerRow?.querySelector('td:nth-child(4)')?.textContent || '';
        const firstName = customerRow?.querySelector('td:nth-child(3)')?.textContent?.split(' ')[0] || '';
        const lastName = customerRow?.querySelector('td:nth-child(3)')?.textContent?.split(' ').slice(1).join(' ') || '';
        const phone = customerRow?.querySelector('td:nth-child(5)')?.textContent || '';
        const status = customerRow?.querySelector('.badge')?.textContent?.toLowerCase() || 'active';

        const res = await fetch(getApiUrl('/api/admin/delete-user.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: customerId })
        });
        
        const result = await res.json();
        
        if (result.success) {
            // Store action for undo with customers array structure
            lastBulkAction = {
                action: 'delete',
                customers: [{
                    id: customerId,
                    fullData: {
                        email: email,
                        first_name: firstName,
                        last_name: lastName,
                        phone: phone
                    },
                    status: status
                }],
                count: 1
            };
            
            showUndoBar('delete', 1);
            
            // Remove the row from table instead of reload
            if (customerRow) {
                customerRow.remove();
            }
        } else {
            alert('✗ Error: ' + result.message);
        }
    } catch (err) {
        alert('✗ Server error. Please try again.');
        console.error(err);
    }
}

function resetPassword(customerId, customerName) {
    resetPasswordUserId = customerId;
    resetPasswordUserName = customerName;
    
    document.getElementById('reset-user-name').textContent = `for ${customerName}`;
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
            showSuccess(result.message);
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
</script>
        </main>
    </div>
    <?php include '../components/toast-notification.php'; ?>
</body>
</html>
