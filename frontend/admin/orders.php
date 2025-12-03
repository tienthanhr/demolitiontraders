<?php
require_once '../config.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Demolition Traders</title>
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
        .bulk-actions { display: flex; gap: 10px; align-items: center; margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .bulk-actions button { margin: 0; }
        .bulk-actions .selected-count { font-weight: 600; color: #2f3192; }
        th.sortable { cursor: pointer; user-select: none; }
        th.sortable:hover { background: #e9ecef; }
        .sort-icon { margin-left: 5px; font-size: 12px; color: #999; }
        .sort-icon.active { color: #2f3192; }
        
        /* Revenue Filter Dropdown */
        .stat-card {
            position: relative;
            overflow: visible !important;
        }
        
        .stats-grid {
            overflow: visible !important;
            z-index: 1;
            margin-bottom: 30px;
        }
        
        .content-section {
            overflow: visible !important;
        }
        
        .revenue-filter-dropdown {
            position: fixed;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            z-index: 999999;
            min-width: 200px;
            overflow: hidden;
            animation: dropdownFadeIn 0.2s ease-out;
        }
        
        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        .filter-option {
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #333;
        }
        .filter-option:last-child {
            border-bottom: none;
        }
        .filter-option:hover {
            background: #f8f9fa;
            padding-left: 24px;
        }
        .filter-option i {
            margin-right: 10px;
            color: #2f3192;
            width: 16px;
        }
        
        /* Custom Date Range Picker */
        .date-range-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            z-index: 9999999;
            min-width: 400px;
        }
        
        .date-range-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999998;
        }
        
        .date-range-modal h4 {
            margin: 0 0 20px 0;
            color: #2f3192;
            font-size: 18px;
        }
        
        .date-range-inputs {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .date-input-group {
            flex: 1;
        }
        
        .date-input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 14px;
            font-weight: 500;
        }
        
        .date-input-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .date-input-group input:focus {
            outline: none;
            border-color: #2f3192;
        }
        
        .date-range-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .date-range-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-apply-date {
            background: #2f3192;
            color: white;
        }
        
        .btn-apply-date:hover {
            background: #1f2170;
        }
        
        .btn-cancel-date {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-cancel-date:hover {
            background: #e0e0e0;
        }
    </style>
    <script src="../assets/js/api-helper.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <?php include 'topbar.php'; ?>

<!-- Orders Management Content -->
<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">All Orders</h2>
        <button class="btn btn-primary" onclick="loadOrders()">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>

    <!-- Statistics -->
    <div class="stats-grid" id="stats-grid">
        <div class="stat-card">
            <i class="fas fa-shopping-bag"></i>
            <h3 id="stat-total">0</h3>
            <p>Total Orders</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-clock"></i>
            <h3 id="stat-pending">0</h3>
            <p>Pending Orders</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-shipping-fast"></i>
            <h3 id="stat-processing">0</h3>
            <p>Processing/Shipped</p>
        </div>
        <div class="stat-card" onclick="toggleRevenueFilter()" style="cursor: pointer;">
            <i class="fas fa-dollar-sign"></i>
            <h3 id="stat-revenue">$0</h3>
            <p id="revenue-period-label">Total Revenue</p>
        </div>
    </div>
    
    <!-- Revenue Filter Dropdown -->
    <div class="revenue-filter-dropdown" id="revenueFilterDropdown" style="display: none;">
        <div class="filter-option" onclick="event.stopPropagation(); setRevenuePeriod('today')">Today</div>
        <div class="filter-option" onclick="event.stopPropagation(); setRevenuePeriod('yesterday')">Yesterday</div>
        <div class="filter-option" onclick="event.stopPropagation(); setRevenuePeriod('this_week')">This Week</div>
        <div class="filter-option" onclick="event.stopPropagation(); setRevenuePeriod('this_month')">This Month</div>
        <div class="filter-option" onclick="event.stopPropagation(); setRevenuePeriod('this_year')">This Year</div>
        <div class="filter-option" onclick="event.stopPropagation(); setRevenuePeriod('all')">All Time</div>
        <div class="filter-option" onclick="event.stopPropagation(); showDatePicker()">
            Custom Date
        </div>
    </div>
    
    <!-- Date Range Modal -->
    <div class="date-range-backdrop" id="dateRangeBackdrop" onclick="closeDateRangeModal()"></div>
    <div class="date-range-modal" id="dateRangeModal">
        <h4>Select Custom Date Range</h4>
        <div class="date-range-inputs">
            <div class="date-input-group">
                <label for="dateFrom">From Date</label>
                <input type="date" id="dateFrom" />
            </div>
            <div class="date-input-group">
                <label for="dateTo">To Date</label>
                <input type="date" id="dateTo" />
            </div>
        </div>
        <div class="date-range-buttons">
            <button class="btn-cancel-date" onclick="closeDateRangeModal()">Cancel</button>
            <button class="btn-apply-date" onclick="applyDateRange()">Apply</button>
        </div>
    </div>

    <!-- Warning Notice -->
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px 20px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-exclamation-triangle" style="color: #856404; font-size: 20px;"></i>
        <div style="flex: 1;">
            <strong style="color: #856404; font-size: 15px;">⚠️ Important Notice:</strong>
            <p style="margin: 5px 0 0 0; color: #856404; font-size: 14px;">Deleted orders <strong>CANNOT be restored</strong> due to data integrity (stock updates, payment records, etc.). Please be careful when deleting orders.</p>
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="search-orders" placeholder="Search by order ID, customer name..." onkeyup="searchOrders()">
        <select id="filter-status" class="form-control" style="max-width: 250px;" onchange="loadOrders()">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="processing">Processing</option>
            <option value="ready">Ready to Ship</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    <!-- Bulk Actions Bar (Products style) -->
    <div id="bulk-actions-bar" style="display: none;">
        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <span id="selected-count">
                <i class="fas fa-check-circle"></i> <span id="selectedCount">0</span> items selected
            </span>
            <select id="bulk-action">
                <option value="">Select Action</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button class="btn btn-light btn-sm" onclick="applyBulkAction()">
                <i class="fas fa-bolt"></i> Apply
            </button>
            <button class="btn btn-secondary btn-sm" onclick="clearSelection()">
                <i class="fas fa-times"></i> Clear
            </button>
        </div>
    </div>

    <!-- Undo Bar -->
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
        <table id="orders-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
                    </th>
                    <th class="sortable" onclick="sortTable('id')" title="Click to sort">
                        Order ID <span id="sort-icon-id" class="sort-icon">↕</span>
                    </th>
                    <th>Customer</th>
                    <th class="sortable" onclick="sortTable('date')" title="Click to sort">
                        Date <span id="sort-icon-date" class="sort-icon">↕</span>
                    </th>
                    <th class="sortable" onclick="sortTable('total')" title="Click to sort">
                        Total <span id="sort-icon-total" class="sort-icon">↕</span>
                    </th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="orders-tbody">
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px;">
                        <div class="spinner"></div>
                        <p>Loading orders...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal" id="order-modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 class="modal-title">Order Details</h3>
            <button class="close-modal" onclick="closeOrderModal()">&times;</button>
        </div>
        <div id="order-details-content">
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading order details...</p>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal" id="status-modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">Update Order Status</h3>
            <button class="close-modal" onclick="closeStatusModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 20px;">Update status for Order <strong id="status-order-id"></strong></p>
            
            <div class="form-group">
                <label for="new-status">Select New Status:</label>
                <select id="new-status" class="form-control" style="width: 100%;">
                    <option value="pending">Pending - Order received, awaiting processing</option>
                    <option value="paid">Paid - Payment confirmed</option>
                    <option value="processing">Processing - Order is being prepared</option>
                    <option value="ready">Ready to Ship - Packed and ready</option>
                    <option value="shipped">Shipped - Order dispatched</option>
                    <option value="delivered">Delivered - Order received by customer</option>
                    <option value="cancelled">Cancelled - Order cancelled</option>
                </select>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label for="status-note">Add Note (Optional):</label>
                <textarea id="status-note" class="form-control" rows="3" placeholder="Enter any notes about this status change..."></textarea>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveOrderStatus()">
                    <i class="fas fa-check"></i> Update Status
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Utility functions
function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = String(d.getFullYear()).slice(-2);
    return `${day}/${month}/${year}`;
}

function formatDateTime(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

// Global variables for sorting
let currentSortColumn = 'date';
let currentSortDirection = 'desc';
let allOrders = [];
let currentRevenuePeriodFilter = null; // { period, customDate }

// Load orders
async function loadOrders() {
    const tbody = document.getElementById('orders-tbody');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px;"><div class="spinner"></div><p>Loading orders...</p></td></tr>';

    try {
        const status = document.getElementById('filter-status').value;
        let url = getApiUrl('/api/index.php?request=orders');
        if (status) url += `&status=${status}`;

        console.log('Fetching orders from:', url);
        const response = await fetch(url);
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        const text = await response.text();
        console.log('Response text:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: red;">Invalid JSON response. Check console.</td></tr>';
            return;
        }
        
        console.log('Parsed data:', data);

        if (data.error) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 40px; color: red;">Error: ${data.error}</td></tr>`;
            return;
        }

        // Handle both array and object with data property
        const orders = Array.isArray(data) ? data : (data.data || []);
        
        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px;">No orders found</td></tr>';
            allOrders = [];
            updateStatistics([]);
            return;
        }

        // Store orders globally and apply current sort
        allOrders = orders;
        updateStatistics(orders);
        
        // Update revenue for current period (default is 'all')
        if (currentRevenuePeriod === 'all') {
            const revenue = orders.reduce((sum, o) => {
                if (o.status !== 'cancelled') {
                    return sum + parseFloat(o.total_amount || o.total || 0);
                }
                return sum;
            }, 0);
            document.getElementById('stat-revenue').textContent = '$' + revenue.toFixed(2);
        }
        
        applySortAndRender();
    } catch (error) {
        console.error('Error loading orders:', error);
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: red;">Error loading orders</td></tr>';
    }
}

// Update statistics
function updateStatistics(orders) {
    const total = orders.length;
    const pending = orders.filter(o => o.status === 'pending' || o.status === '').length;
    const processing = orders.filter(o => ['processing', 'ready', 'shipped'].includes(o.status)).length;
    
    // Only update these stats, not revenue (revenue is updated separately)
    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-pending').textContent = pending;
    document.getElementById('stat-processing').textContent = processing;
    
    // Update revenue based on current period if not already set
    if (!document.getElementById('stat-revenue').textContent.includes('$')) {
        const revenue = orders.reduce((sum, o) => sum + parseFloat(o.total_amount || 0), 0);
        document.getElementById('stat-revenue').textContent = '$' + revenue.toFixed(2);
    }
}

// Bulk actions
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.order-checkbox:checked');
    const count = checkboxes.length;
    const bulkBar = document.getElementById('bulk-actions-bar');
    const selectedCountSpan = document.getElementById('selectedCount');
    const selectAll = document.getElementById('select-all');
    
    selectedCountSpan.textContent = count;
    bulkBar.style.display = count > 0 ? 'block' : 'none';
    
    const allCheckboxes = document.querySelectorAll('.order-checkbox');
    selectAll.checked = count > 0 && count === allCheckboxes.length;
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkActions();
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('select-all').checked = false;
    updateBulkActions();
}

function applyBulkAction() {
    const action = document.getElementById('bulk-action').value;
    if (!action) {
        showWarning('Please select an action');
        return;
    }
    
    if (action === 'delete') {
        bulkDeleteOrders();
    }
}

// Undo functionality
let lastBulkAction = null;
let undoTimeout = null;

function showUndoBar(action, count) {
    const undoBar = document.getElementById('undo-bar');
    const undoMessage = document.getElementById('undo-message');
    
    undoMessage.innerHTML = `<i class="fas fa-info-circle"></i> ${count} order(s) ${action}d`;
    undoBar.style.display = 'flex';
    
    if (undoTimeout) clearTimeout(undoTimeout);
    undoTimeout = setTimeout(() => {
        dismissUndo();
    }, 10000);
}

function dismissUndo() {
    const undoBar = document.getElementById('undo-bar');
    undoBar.style.display = 'none';
    lastBulkAction = null;
    if (undoTimeout) {
        clearTimeout(undoTimeout);
        undoTimeout = null;
    }
}

async function undoLastAction() {
    if (!lastBulkAction) return;
    
    const { action, orders } = lastBulkAction;
    dismissUndo();
    
    if (action === 'delete') {
        // Note: Orders cannot be restored due to data integrity
        // (stock quantities, cart items, payment records, etc.)
        showWarning('Orders cannot be restored due to data integrity constraints.');
    }
}

async function bulkDeleteOrders() {
    const checkboxes = document.querySelectorAll('.order-checkbox:checked');
    const orderIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (orderIds.length === 0) {
        showWarning('Please select orders to delete');
        return;
    }
    
    const confirmed = await showConfirm(
        `Are you sure you want to delete ${orderIds.length} order(s)? This action cannot be undone.`,
        'Delete Orders',
        true
    );
    if (!confirmed) return;
    
    try {
        let successCount = 0;
        let failCount = 0;
        const deletedOrders = [];
        
        // First, fetch all order data before deleting
        for (const orderId of orderIds) {
            try {
                const orderRes = await fetch((()=>{const p=`/api/index.php?request=orders/${orderId}`;return getApiUrl(p);})());
                if (orderRes.ok) {
                    const orderData = await orderRes.json();
                    const order = orderData.data || orderData;
                    
                    // Fetch order items
                    const itemsRes = await fetch((()=>{const p=`/api/index.php?request=orders/${orderId}/items`;return getApiUrl(p);})());
                    const itemsData = await itemsRes.json();
                    order.items = itemsData.data || itemsData;
                    
                    deletedOrders.push(order);
                }
            } catch (err) {
                console.error('Error fetching order data', orderId, err);
            }
        }
        
        // Now delete the orders
        for (const orderId of orderIds) {
            try {
                const res = await fetch((()=>{const p=`/api/index.php?request=orders&id=${orderId}`;return getApiUrl(p);})(), {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                if (!res.ok) {
                    failCount++;
                    console.error('Failed to delete order', orderId, 'Status:', res.status);
                    continue;
                }
                
                const text = await res.text();
                if (!text) {
                    failCount++;
                    console.error('Empty response for order', orderId);
                    continue;
                }
                
                const result = JSON.parse(text);
                if (result.success) {
                    successCount++;
                } else {
                    failCount++;
                    console.error('Failed to delete order', orderId, result);
                }
            } catch (err) {
                failCount++;
                console.error('Error deleting order', orderId, err);
            }
        }
        
        // Note: Undo is not available for orders due to data integrity
        // (stock updates, payment records, etc.)
        
        if (failCount > 0) {
            showError(`${successCount} deleted, ${failCount} failed`);
        } else {
            showSuccess(`Deleted ${successCount} order(s)`);
        }
        loadOrders();
        
    } catch (err) {
        showError('Server error. Please try again.');
        console.error(err);
    }
}

// Sort table
function sortTable(column) {
    if (currentSortColumn === column) {
        // Toggle direction if same column
        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        // New column, default to descending
        currentSortColumn = column;
        currentSortDirection = 'desc';
    }
    
    applySortAndRender();
}

// Apply sort and render
function applySortAndRender() {
    if (!allOrders || allOrders.length === 0) return;
    
    // Apply revenue period filter if active
    let ordersToDisplay = allOrders;
    if (currentRevenuePeriodFilter) {
        ordersToDisplay = filterOrdersByPeriod(allOrders, currentRevenuePeriodFilter.period, currentRevenuePeriodFilter.customDate);
    }
    
    // Sort the orders
    const sortedOrders = [...ordersToDisplay].sort((a, b) => {
        let aVal, bVal;
        
        switch(currentSortColumn) {
            case 'id':
                aVal = parseInt(a.id);
                bVal = parseInt(b.id);
                break;
            case 'total':
                aVal = parseFloat(a.total_amount);
                bVal = parseFloat(b.total_amount);
                break;
            case 'date':
                aVal = new Date(a.created_at).getTime();
                bVal = new Date(b.created_at).getTime();
                break;
            default:
                return 0;
        }
        
        // Compare values
        return currentSortDirection === 'asc' 
            ? aVal - bVal
            : bVal - aVal;
    });
    
    // Update sort icons
    updateSortIcons();
    
    // Render sorted orders
    renderOrders(sortedOrders);
}

// Update sort icons
function updateSortIcons() {
    // Reset all icons
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.innerHTML = '↕';
        icon.classList.remove('active');
    });
    
    // Highlight active column
    const activeIcon = document.getElementById(`sort-icon-${currentSortColumn}`);
    if (activeIcon) {
        activeIcon.innerHTML = currentSortDirection === 'asc' ? '↑' : '↓';
        activeIcon.classList.add('active');
    }
}

// Render orders
function renderOrders(orders) {
    const tbody = document.getElementById('orders-tbody');
    
    tbody.innerHTML = orders.map(order => {
            // Parse billing address to get customer info
            let customerName = 'Guest';
            let customerEmail = order.guest_email || '';
            
            try {
                const billing = JSON.parse(order.billing_address || '{}');
                customerName = `${billing.first_name || ''} ${billing.last_name || ''}`.trim() || 'Guest';
                customerEmail = billing.email || order.guest_email || '';
            } catch (e) {
                console.error('Failed to parse billing address:', e);
            }
            
            // Get badge colors
            const badgeColors = {
                'pending': { bg: '#ffc107', color: '#000' },
                'paid': { bg: '#28a745', color: 'white' },
                'processing': { bg: '#17a2b8', color: 'white' },
                'ready': { bg: '#6f42c1', color: 'white' },
                'shipped': { bg: '#007bff', color: 'white' },
                'delivered': { bg: '#20c997', color: 'white' },
                'cancelled': { bg: '#dc3545', color: 'white' }
            };
            
            // Handle empty string as 'pending'
            const orderStatus = order.status && order.status.trim() !== '' ? order.status : 'pending';
            const statusColor = badgeColors[orderStatus] || { bg: '#6c757d', color: 'white' };
            const statusText = orderStatus.toUpperCase();
            
            console.log('Order status:', order.status, 'Normalized:', orderStatus, 'Text:', statusText);
            
            return `
            <tr data-order-id="${order.id}">
                <td data-label="Select"><input type="checkbox" class="order-checkbox" value="${order.id}" onchange="updateBulkActions()"></td>
                <td data-label="Order ID"><strong>#${order.id}</strong></td>
                <td data-label="Customer">${customerName}<br><small>${customerEmail}</small></td>
                <td data-label="Date">${formatDate(order.created_at)}</td>
                <td data-label="Total"><strong>$${parseFloat(order.total_amount).toFixed(2)}</strong></td>
                <td data-label="Status">
                    <div onclick="updateOrderStatus(${order.id})" style="cursor: pointer !important; display: inline-block !important; padding: 6px 12px !important; border-radius: 12px !important; font-size: 12px !important; font-weight: 700 !important; text-transform: uppercase !important; background-color: ${statusColor.bg} !important; color: ${statusColor.color} !important; line-height: normal !important; white-space: nowrap !important; text-align: center !important; vertical-align: middle !important; min-width: 80px !important;" title="Click to change status">${statusText}</div>
                </td>
                <td data-label="Payment">${order.payment_method || 'N/A'}</td>
                <td data-label="Actions">
                    <div class="action-btns">
                        <button class="btn btn-primary btn-sm" onclick="viewOrder(${order.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-success btn-sm" onclick="printOrder(${order.id}, '${orderStatus}')" title="Print Invoice/Receipt">
                            <i class="fas fa-print"></i>
                        </button>
                        ${orderStatus === 'paid' || orderStatus === 'delivered' || orderStatus === 'completed' ? `
                        <button class="btn btn-info btn-sm" onclick="sendReceipt(${order.id})" title="Send Receipt Email">
                            <i class="fas fa-envelope"></i>
                        </button>
                        ` : ''}
                        ${orderStatus === 'pending' || orderStatus === 'processing' || orderStatus === 'ready' || orderStatus === 'shipped' || orderStatus === 'refunded' || orderStatus === 'cancelled' ? `
                        <button class="btn btn-secondary btn-sm" onclick="sendTaxInvoice(${order.id})" title="Send Tax Invoice Email">
                            <i class="fas fa-file-invoice"></i>
                        </button>
                        ` : ''}
                        <button class="btn btn-warning btn-sm" onclick="updateOrderStatus(${order.id})" title="Update Status">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteOrder(${order.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        }).join('');
}

// Search orders
function searchOrders() {
    const search = document.getElementById('search-orders').value.toLowerCase();
    const rows = document.querySelectorAll('#orders-tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
}

// View order details
async function viewOrder(id) {
    const modal = document.getElementById('order-modal');
    const content = document.getElementById('order-details-content');
    
    modal.classList.add('active');
    content.innerHTML = '<div class="loading"><div class="spinner"></div><p>Loading order details...</p></div>';

    try {
        const response = await fetch((()=>{const p=`/api/index.php?request=orders/${id}`;return getApiUrl(p);})());
        const order = await response.json();

        // Parse billing and shipping addresses
        let billing = {};
        let shipping = {};
        
        try {
            billing = JSON.parse(order.billing_address || '{}');
        } catch (e) {
            console.error('Failed to parse billing address:', e);
        }
        
        try {
            shipping = JSON.parse(order.shipping_address || '{}');
        } catch (e) {
            console.error('Failed to parse shipping address:', e);
        }

        // Format customer name
        const customerName = `${billing.first_name || ''} ${billing.last_name || ''}`.trim() || 'Guest';
        const customerEmail = billing.email || order.guest_email || 'N/A';
        const customerPhone = billing.phone || 'N/A';

        // Format shipping address
        const shippingAddress = shipping.address 
            ? `${shipping.address}${shipping.city ? ', ' + shipping.city : ''}${shipping.postcode ? ' ' + shipping.postcode : ''}`
            : 'Same as billing address';

        content.innerHTML = `
            <div style="margin-bottom: 20px;">
                <h4>Order #${order.id}</h4>
                <p><strong>Order Number:</strong> ${order.order_number}</p>
                <p><strong>Date:</strong> ${formatDateTime(order.created_at)}</p>
                <p><strong>Status:</strong> <span class="badge badge-${order.status}">${(order.status || 'pending').toUpperCase()}</span></p>
            </div>

            <div style="margin-bottom: 20px;">
                <h4>Customer Information</h4>
                <p><strong>Name:</strong> ${customerName}</p>
                <p><strong>Email:</strong> ${customerEmail}</p>
                <p><strong>Phone:</strong> ${customerPhone}</p>
            </div>

            <div style="margin-bottom: 20px;">
                <h4>Billing Address</h4>
                <p>${billing.address || 'N/A'}</p>
                ${billing.city ? `<p>${billing.city}${billing.postcode ? ' ' + billing.postcode : ''}</p>` : ''}
            </div>

            <div style="margin-bottom: 20px;">
                <h4>Shipping Address</h4>
                <p>${shipping.address || billing.address || 'N/A'}</p>
                ${(shipping.city || billing.city) ? `<p>${shipping.city || billing.city}${(shipping.postcode || billing.postcode) ? ' ' + (shipping.postcode || billing.postcode) : ''}</p>` : ''}
            </div>

            <div class="order-items">
                <h4>Order Items</h4>
                ${order.items ? order.items.map(item => `
                    <div class="order-item">
                        <div>
                            <strong>${item.product_name}</strong><br>
                            <small>SKU: ${item.sku}</small>
                        </div>
                        <div style="text-align: right;">
                            <div>Qty: ${item.quantity}</div>
                            <div><strong>$${parseFloat(item.unit_price * item.quantity).toFixed(2)}</strong></div>
                        </div>
                    </div>
                `).join('') : '<p>No items</p>'}
            </div>

            <div class="order-summary">
                <div class="order-summary-row">
                    <strong>Subtotal:</strong>
                    <span>$${parseFloat(order.subtotal || 0).toFixed(2)}</span>
                </div>
                <div class="order-summary-row">
                    <strong>Tax (GST):</strong>
                    <span>$${parseFloat(order.tax_amount || 0).toFixed(2)}</span>
                </div>
                <div class="order-summary-row">
                    <strong>Shipping:</strong>
                    <span>$${parseFloat(order.shipping_amount || 0).toFixed(2)}</span>
                </div>
                <div class="order-summary-row" style="font-size: 18px; color: #2f3192; margin-top: 10px; padding-top: 10px; border-top: 2px solid #dee2e6;">
                    <strong>Total:</strong>
                    <strong>$${parseFloat(order.total_amount).toFixed(2)}</strong>
                </div>
            </div>

            ${order.customer_notes ? `
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h4 style="margin-bottom: 10px;">Customer Notes</h4>
                <p>${order.customer_notes}</p>
            </div>
            ` : ''}

            ${order.admin_notes ? `
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px;">
                <h4 style="margin-bottom: 10px;">Admin Notes</h4>
                <p>${order.admin_notes}</p>
            </div>
            ` : ''}

            <div style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="closeOrderModal()">Close</button>
            </div>
        `;
    } catch (error) {
        console.error('Error loading order details:', error);
        content.innerHTML = '<p style="color: red;">Error loading order details</p>';
    }
}

// Close order modal
function closeOrderModal() {
    document.getElementById('order-modal').classList.remove('active');
}

// Get status icon
function getStatusIcon(status) {
    const icons = {
        'pending': '📋',
        'paid': '💰',
        'processing': '⚙️',
        'ready': '📦',
        'shipped': '🚚',
        'delivered': '✅',
        'cancelled': '❌'
    };
    return icons[status] || '📋';
}

// Update order status - open modal
let currentOrderId = null;

async function updateOrderStatus(id) {
    currentOrderId = id;
    document.getElementById('status-order-id').textContent = `#${id}`;
    
    // Get current order status
    try {
        const response = await fetch((()=>{const p=`/api/index.php?request=orders/${id}`;return getApiUrl(p);})());
        const order = await response.json();
        document.getElementById('new-status').value = order.status;
    } catch (error) {
        console.error('Error fetching order:', error);
    }
    
    document.getElementById('status-note').value = '';
    document.getElementById('status-modal').classList.add('active');
}

// Close status modal
function closeStatusModal() {
    document.getElementById('status-modal').classList.remove('active');
    currentOrderId = null;
}

// Save order status
async function saveOrderStatus() {
    if (!currentOrderId) {
        console.error('No order ID set');
        alert('Error: No order selected');
        return;
    }
    
    const newStatus = document.getElementById('new-status').value;
    const note = document.getElementById('status-note').value;
    
    if (!newStatus) {
        alert('Please select a status');
        return;
    }
    
    try {
        console.log('Updating order:', currentOrderId, 'to status:', newStatus);
        
        const requestBody = { 
            status: newStatus
        };
        
        if (note && note.trim() !== '') {
            requestBody.note = note;
        }
        
        console.log('Request body:', requestBody);
        
        const apiPath = `/api/index.php?request=orders/${currentOrderId}`;
        const response = await fetch(getApiUrl(apiPath), {
            method: 'PUT',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });

        console.log('Update response status:', response.status);
        console.log('Update response ok:', response.ok);
        
        const text = await response.text();
        console.log('Update response text:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was not valid JSON:', text);
            alert('Error: Invalid response from server. Check console for details.');
            return;
        }

        console.log('Parsed response data:', data);

        if (response.ok && !data.error) {
            closeStatusModal();
            loadOrders();
            
            // Show success message
            const successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success';
            successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 20px; background: #28a745; color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
            successMsg.innerHTML = `<i class="fas fa-check-circle"></i> Order status updated successfully to ${newStatus.toUpperCase()}!`;
            document.body.appendChild(successMsg);
            setTimeout(() => successMsg.remove(), 3000);
        } else {
            const errorMsg = data.error || data.message || 'Unknown error occurred';
            console.error('Update failed:', errorMsg);
            alert('Error updating order status: ' + errorMsg);
        }
    } catch (error) {
        console.error('Update error:', error);
        alert('Error: ' + error.message);
    }
}

// Print order
async function printOrder(id, status) {
    try {
        const apiPath = `/api/index.php?request=orders/${id}`;
        const response = await fetch(getApiUrl(apiPath));
        const order = await response.json();
        
        // Parse addresses
        let billing = {};
        try {
            billing = JSON.parse(order.billing_address || '{}');
        } catch (e) {
            console.error('Failed to parse billing address:', e);
        }
        
        // Determine if this is a paid order (Receipt) or pending (Tax Invoice)
        const isPaid = status === 'paid' || status === 'delivered' || status === 'completed';
        
        // Create print window
        const printWindow = window.open('', '', 'width=800,height=600');
        
        // Generate print content based on status
        const printContent = isPaid ? generateReceipt(order, billing) : generateTaxInvoice(order, billing);
        
        printWindow.document.write(printContent);
        printWindow.document.close();
        
        // Wait for content to load then print
        setTimeout(() => {
            printWindow.print();
        }, 250);
    } catch (error) {
        console.error('Error printing order:', error);
        alert('Error printing order: ' + error.message);
    }
}

// Generate Tax Invoice (for pending orders)
function generateTaxInvoice(order, billing) {
    const customerName = `${billing.first_name || ''} ${billing.last_name || ''}`.trim() || 'Guest';
    const orderDate = new Date(order.created_at);
    
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Tax Invoice - Order #${order.order_number}</title>
            <style>
                @page {
                    size: A4;
                    margin: 20mm;
                }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
                body {
                    font-family: Arial, sans-serif;
                    max-width: 210mm;
                    margin: 0 auto;
                    padding: 20px;
                    font-size: 11pt;
                    line-height: 1.4;
                }
                .header {
                    display: flex;
                    justify-content: space-between;
                    border-bottom: 3px solid #2f3192;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .company-info {
                    flex: 1;
                }
                .company-name {
                    font-size: 24pt;
                    font-weight: bold;
                    color: #2f3192;
                    margin-bottom: 10px;
                }
                .company-details {
                    font-size: 10pt;
                    line-height: 1.6;
                }
                .invoice-info {
                    text-align: right;
                }
                .invoice-title {
                    font-size: 28pt;
                    font-weight: bold;
                    color: #2f3192;
                    margin-bottom: 10px;
                }
                .invoice-meta {
                    font-size: 10pt;
                }
                .bill-to-section {
                    margin: 30px 0;
                    padding: 15px;
                    background: #f8f9fa;
                    border-left: 4px solid #2f3192;
                }
                .bill-to-title {
                    font-weight: bold;
                    font-size: 12pt;
                    margin-bottom: 10px;
                    color: #2f3192;
                }
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 30px 0;
                }
                .items-table thead {
                    background: #2f3192;
                    color: white;
                }
                .items-table th {
                    padding: 12px;
                    text-align: left;
                    font-weight: bold;
                }
                .items-table td {
                    padding: 12px;
                    border-bottom: 1px solid #dee2e6;
                }
                .items-table tr:hover {
                    background: #f8f9fa;
                }
                .text-right {
                    text-align: right;
                }
                .totals-section {
                    margin-top: 30px;
                    display: flex;
                    justify-content: flex-end;
                }
                .totals-box {
                    width: 300px;
                }
                .total-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 8px 0;
                    border-bottom: 1px solid #dee2e6;
                }
                .grand-total {
                    font-size: 18pt;
                    font-weight: bold;
                    background: #2f3192;
                    color: white;
                    padding: 15px;
                    margin-top: 10px;
                }
                .gst-breakdown {
                    margin-top: 20px;
                    padding: 15px;
                    background: #f8f9fa;
                    border: 1px solid #dee2e6;
                }
                .gst-breakdown h4 {
                    margin: 0 0 10px 0;
                    color: #2f3192;
                }
                .payment-info {
                    margin: 40px 0;
                    padding: 20px;
                    background: #fff3cd;
                    border: 2px solid #ffc107;
                    border-radius: 5px;
                }
                .payment-info h4 {
                    margin: 0 0 15px 0;
                    color: #856404;
                }
                .bank-details {
                    display: grid;
                    grid-template-columns: 150px 1fr;
                    gap: 8px;
                    font-size: 10pt;
                }
                .bank-details strong {
                    color: #856404;
                }
                .terms {
                    margin-top: 40px;
                    padding: 20px;
                    background: #f8f9fa;
                    border-top: 2px solid #dee2e6;
                    font-size: 9pt;
                    line-height: 1.6;
                }
                .terms h4 {
                    margin: 0 0 15px 0;
                    color: #2f3192;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 9pt;
                    color: #6c757d;
                    border-top: 2px solid #dee2e6;
                    padding-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-info">
                    <div class="company-name">Demolition Traders</div>
                    <div class="company-details">
                        249 Kahikatea Drive<br>
                        Hamilton 3204<br>
                        Phone: 07-847-4989<br>
                        Email: admin@demolitiontraders.co.nz<br>
                        <strong>GST Number: 45-514-609</strong>
                    </div>
                </div>
                <div class="invoice-info">
                    <div class="invoice-title">TAX INVOICE</div>
                    <div class="invoice-meta">
                        <strong>Invoice #:</strong> ${order.order_number}<br>
                        <strong>Date:</strong> ${formatDate(order.created_at)}<br>
                        <strong>Time:</strong> ${orderDate.toLocaleTimeString('en-NZ')}
                    </div>
                </div>
            </div>
            
            <div class="bill-to-section">
                <div class="bill-to-title">BILL TO:</div>
                <strong>${customerName}</strong><br>
                ${billing.address || ''}<br>
                ${billing.city || ''} ${billing.postcode || ''}<br>
                Email: ${billing.email || order.guest_email || 'N/A'}<br>
                Phone: ${billing.phone || 'N/A'}
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 15%;">SKU</th>
                        <th style="width: 10%;" class="text-right">Qty</th>
                        <th style="width: 12%;" class="text-right">Unit Price</th>
                        <th style="width: 13%;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${order.items.map(item => `
                        <tr>
                            <td><strong>${item.product_name}</strong></td>
                            <td>${item.sku}</td>
                            <td class="text-right">${item.quantity}</td>
                            <td class="text-right">$${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td class="text-right"><strong>$${parseFloat(item.subtotal).toFixed(2)}</strong></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            
            <div class="totals-section">
                <div class="totals-box">
                    <div class="grand-total">
                        <div style="display: flex; justify-content: space-between;">
                            <span>TOTAL AMOUNT DUE</span>
                            <span>$${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                    </div>
                    
                    <div class="gst-breakdown">
                        <h4>GST Breakdown</h4>
                        <div class="total-row">
                            <span>Subtotal (excl GST):</span>
                            <span>$${parseFloat(order.subtotal).toFixed(2)}</span>
                        </div>
                        <div class="total-row">
                            <span>GST Amount (15%):</span>
                            <span>$${parseFloat(order.tax_amount).toFixed(2)}</span>
                        </div>
                        ${parseFloat(order.shipping_amount) > 0 ? `
                        <div class="total-row">
                            <span>Shipping:</span>
                            <span>$${parseFloat(order.shipping_amount).toFixed(2)}</span>
                        </div>
                        ` : ''}
                        <div class="total-row" style="font-weight: bold; font-size: 12pt;">
                            <span>Total (incl GST):</span>
                            <span>$${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="payment-info">
                <h4>PAYMENT INFORMATION</h4>
                <div class="bank-details">
                    <strong>Bank:</strong> <span>BNZ (Bank of New Zealand)</span>
                    <strong>Account Name:</strong> <span>Demolition Traders</span>
                    <strong>Account Number:</strong> <span>02-0341-0083457-00</span>
                    <strong>Reference:</strong> <span>${billing.last_name || 'Customer'}</span>
                </div>
                <p style="margin-top: 15px; font-size: 10pt;"><strong>Please use your last name as payment reference.</strong></p>
            </div>
            
            <div class="terms">
                <h4>TERMS & CONDITIONS</h4>
                <p><strong>Payment Terms:</strong> Payment due within 7 days of invoice date.</p>
                <p><strong>Refund Policy:</strong> Demolition Traders Ltd offers a 30 day refund period on all goods. Goods must be returned and inspected within 30 days of original purchase date for a refund. Proof of original purchase is required as a condition of the returns policy.</p>
                <p><strong>Returns:</strong> Goods must be returned in the original purchase condition and be unused and undamaged. Any items with scratch or scuff marks and that have been cut down or altered will not be refunded - whether in transit or during 3rd party handling.</p>
                <p><strong>Non-Refundable Items:</strong> Items with custom liners are non-refundable. Any credit card transaction fees are non-refundable.</p>
            </div>
            
            <div class="footer">
                Thank you for your business!<br>
                For any queries, please contact us at 07-847-4989 or info@demolitiontraders.co.nz
            </div>
            
            <div class="no-print" style="margin-top: 20px; text-align: center;">
                <button onclick="window.print()" style="padding: 15px 30px; font-size: 14px; background: #2f3192; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Invoice</button>
                <button onclick="window.close()" style="padding: 15px 30px; font-size: 14px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Close</button>
            </div>
        </body>
        </html>
    `;
}

// Generate Receipt (for paid orders)
function generateReceipt(order, billing) {
    const customerName = `${billing.first_name || ''} ${billing.last_name || ''}`.trim() || 'Guest';
    const orderDate = new Date(order.created_at);
    const paidDate = order.payment_status === 'paid' && order.updated_at ? new Date(order.updated_at) : orderDate;

    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt - Order #${order.order_number}</title>
            <style>
                @page {
                    size: A4;
                    margin: 20mm;
                }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
                body {
                    font-family: Arial, sans-serif;
                    max-width: 210mm;
                    margin: 0 auto;
                    padding: 20px;
                    font-size: 11pt;
                    line-height: 1.4;
                }
                .header {
                    display: flex;
                    justify-content: space-between;
                    border-bottom: 3px solid #2f3192;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .company-info {
                    flex: 1;
                }
                .company-name {
                    font-size: 24pt;
                    font-weight: bold;
                    color: #2f3192;
                    margin-bottom: 10px;
                }
                .company-details {
                    font-size: 10pt;
                    line-height: 1.6;
                }
                .invoice-info {
                    text-align: right;
                }
                .invoice-title {
                    font-size: 28pt;
                    font-weight: bold;
                    color: #2f3192;
                    margin-bottom: 10px;
                    text-transform: uppercase;
                }
                .invoice-meta {
                    font-size: 10pt;
                }
                .bill-to-section {
                    margin: 30px 0;
                    padding: 15px;
                    background: #f8f9fa;
                    border-left: 4px solid #2f3192;
                }
                .bill-to-title {
                    font-weight: bold;
                    font-size: 12pt;
                    margin-bottom: 10px;
                    color: #2f3192;
                }
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 30px 0;
                }
                .items-table thead {
                    background: #2f3192;
                    color: white;
                }
                .items-table th {
                    padding: 12px;
                    text-align: left;
                    font-weight: bold;
                }
                .items-table td {
                    padding: 12px;
                    border-bottom: 1px solid #dee2e6;
                }
                .items-table tr:hover {
                    background: #f8f9fa;
                }
                .text-right {
                    text-align: right;
                }

                .totals-section {
                    margin-top: 30px;
                    display: flex;
                    justify-content: flex-end;
                }
                .totals-box {
                    width: 300px;
                }
                .total-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 8px 0;
                    border-bottom: 1px solid #dee2e6;
                }
                .grand-total {
                    font-size: 18pt;
                    font-weight: bold;
                    background: #2f3192;
                    color: white;
                    padding: 15px;
                    margin-top: 10px;
                }
                .gst-breakdown {
                    margin-top: 20px;
                    padding: 15px;
                    background: #f8f9fa;
                    border: 1px solid #dee2e6;
                }
                .gst-breakdown h4 {
                    margin: 0 0 10px 0;
                    color: #2f3192;
                }

                .paid-box {
                    margin: 40px 0;
                    padding: 20px;
                    background: #d4edda;
                    border: 2px solid #28a745;
                    border-radius: 5px;
                    text-align: center;
                    font-size: 14pt;
                    font-weight: bold;
                    color: #155724;
                }

                .terms {
                    margin-top: 40px;
                    padding: 20px;
                    background: #f8f9fa;
                    border-top: 2px solid #dee2e6;
                    font-size: 9pt;
                    line-height: 1.6;
                }
                .terms h4 {
                    margin: 0 0 15px 0;
                    color: #2f3192;
                }

                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 9pt;
                    color: #6c757d;
                    border-top: 2px solid #dee2e6;
                    padding-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-info">
                    <div class="company-name">Demolition Traders</div>
                    <div class="company-details">
                        249 Kahikatea Drive<br>
                        Hamilton 3204<br>
                        Phone: 07-847-4989<br>
                        Email: admin@demolitiontraders.co.nz<br>
                        <strong>GST Number: 45-514-609</strong>
                    </div>
                </div>
                <div class="invoice-info">
                    <div class="invoice-title">RECEIPT</div>
                    <div class="invoice-meta">
                        <strong>Receipt #:</strong> ${order.order_number}<br>
                        <strong>Date:</strong> ${formatDate(order.created_at)}<br>
                        <strong>Time:</strong> ${paidDate.toLocaleTimeString('en-NZ')}
                    </div>
                </div>
            </div>

            <div class="bill-to-section">
                <div class="bill-to-title">CUSTOMER:</div>
                <strong>${customerName}</strong><br>
                ${billing.address || ''}<br>
                ${billing.city || ''} ${billing.postcode || ''}<br>
                Email: ${billing.email || order.guest_email || 'N/A'}<br>
                Phone: ${billing.phone || 'N/A'}
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 15%;">SKU</th>
                        <th style="width: 10%;" class="text-right">Qty</th>
                        <th style="width: 12%;" class="text-right">Unit Price</th>
                        <th style="width: 13%;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${order.items.map(item => `
                        <tr>
                            <td><strong>${item.product_name}</strong></td>
                            <td>${item.sku}</td>
                            <td class="text-right">${item.quantity}</td>
                            <td class="text-right">$${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td class="text-right"><strong>$${parseFloat(item.subtotal).toFixed(2)}</strong></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>

            <div class="totals-section">
                <div class="totals-box">
                    <div class="grand-total">
                        <div style="display: flex; justify-content: space-between;">
                            <span>TOTAL PAID</span>
                            <span>$${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                    </div>

                    <div class="gst-breakdown">
                        <h4>GST Breakdown</h4>
                        <div class="total-row">
                            <span>Subtotal (excl GST):</span>
                            <span>$${parseFloat(order.subtotal).toFixed(2)}</span>
                        </div>
                        <div class="total-row">
                            <span>GST Amount (15%):</span>
                            <span>$${parseFloat(order.tax_amount).toFixed(2)}</span>
                        </div>
                        ${parseFloat(order.shipping_amount) > 0 ? `
                        <div class="total-row">
                            <span>Shipping:</span>
                            <span>$${parseFloat(order.shipping_amount).toFixed(2)}</span>
                        </div>
                        ` : ''}
                        <div class="total-row" style="font-weight: bold; font-size: 12pt;">
                            <span>Total (incl GST):</span>
                            <span>$${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="paid-box">PAID IN FULL</div>

            <div class="terms">
                <h4>TERMS & CONDITIONS</h4>
                <p><strong>Payment Terms:</strong> Payment has been received in full for goods listed above.</p>
                <p><strong>Refund Policy:</strong> Demolition Traders Ltd offers a 30 day refund period on all goods. Goods must be returned and inspected within 30 days of original purchase date for a refund. Proof of original purchase is required as a condition of the returns policy.</p>
                <p><strong>Returns:</strong> Goods must be returned in the original purchase condition and be unused and undamaged. Any items with scratch or scuff marks and that have been cut down or altered will not be refunded - whether in transit or during 3rd party handling.</p>
                <p><strong>Non-Refundable Items:</strong> Items with custom liners are non-refundable. Any credit card transaction fees are non-refundable.</p>
            </div>

            <div class="footer">
                Thank you for your purchase!<br>
                For any queries, please contact 07-847-4989 or info@demolitiontraders.co.nz
            </div>

            <div class="no-print" style="margin-top: 20px; text-align: center;">
                <button onclick="window.print()" style="padding: 15px 30px; font-size: 14px; background: #2f3192; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Receipt</button>
                <button onclick="window.close()" style="padding: 15px 30px; font-size: 14px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Close</button>
            </div>
        </body>
        </html>
    `;
}


// Delete order
async function deleteOrder(id) {
    const confirmed = await showConfirm(
        `Are you sure you want to delete Order #${id}? This action cannot be undone.`,
        'Delete Order',
        true
    );
    if (!confirmed) return;
    
    try {
        const response = await fetch((()=>{const p=`/api/index.php?request=orders/${id}`;return getApiUrl(p);})(), {
            method: 'DELETE'
        });

        if (response.ok) {
            loadOrders();
            
            // Show success message
            const successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success';
            successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 20px; background: #28a745; color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
            successMsg.innerHTML = `<i class="fas fa-check"></i> Order deleted successfully!`;
            document.body.appendChild(successMsg);
            setTimeout(() => successMsg.remove(), 3000);
        } else {
            alert('Error deleting order');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Send Receipt Email
async function sendReceipt(id) {
    if (!confirm(`Send receipt email for Order #${id}?`)) {
        return;
    }
    
    try {
        // Show sending message
        const sendingMsg = document.createElement('div');
        sendingMsg.className = 'alert alert-info';
        sendingMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 20px; background: #17a2b8; color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
        sendingMsg.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Sending receipt email...`;
        document.body.appendChild(sendingMsg);
        
        const response = await fetch((()=>{const p=`/api/index.php?request=orders/${id}/send-receipt`;return getApiUrl(p);})(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        sendingMsg.remove();

        if (response.ok) {
            try {
                const result = await response.json();
                
                // Show success message
                const successMsg = document.createElement('div');
                successMsg.className = 'alert alert-success';
                successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 20px; background: #28a745; color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
                successMsg.innerHTML = `<i class="fas fa-check-circle"></i> Receipt email sent successfully!`;
                document.body.appendChild(successMsg);
                setTimeout(() => successMsg.remove(), 3000);
            } catch (jsonError) {
                // Success but no JSON response
                const successMsg = document.createElement('div');
                successMsg.className = 'alert alert-success';
                successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 20px; background: #28a745; color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
                successMsg.innerHTML = `<i class="fas fa-check-circle"></i> Receipt email sent successfully!`;
                document.body.appendChild(successMsg);
                setTimeout(() => successMsg.remove(), 3000);
            }
        } else {
            try {
                const error = await response.json();
                alert('Error sending receipt: ' + (error.error || 'Unknown error'));
            } catch (jsonError) {
                const text = await response.text();
                alert('Error sending receipt: ' + (text || 'Unknown error'));
            }
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Send Tax Invoice Email
async function sendTaxInvoice(id) {
    if (!confirm(`Send tax invoice email for Order #${id}?`)) {
        return;
    }
    try {
        const sendingMsg = document.createElement('div');
        sendingMsg.className = 'alert alert-info';
        sendingMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 20px; background: #6c757d; color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
        sendingMsg.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Sending tax invoice email...`;
        document.body.appendChild(sendingMsg);

        const response = await fetch((()=>{const p=`/api/index.php?request=orders/${id}/send-tax-invoice`;return getApiUrl(p);})(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        sendingMsg.remove();

        if (response.ok) {
            try {
                const result = await response.json();
                const successMsg = document.createElement('div');
                successMsg.className = 'alert alert-success';
                successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 20px; background: #28a745; color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
                successMsg.innerHTML = `<i class="fas fa-check-circle"></i> Tax invoice email sent successfully!`;
                document.body.appendChild(successMsg);
                setTimeout(() => successMsg.remove(), 3000);
            } catch (jsonError) {
                // Success but no JSON response
                const successMsg = document.createElement('div');
                successMsg.className = 'alert alert-success';
                successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 20px; background: #28a745; color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
                successMsg.innerHTML = `<i class="fas fa-check-circle"></i> Tax invoice email sent successfully!`;
                document.body.appendChild(successMsg);
                setTimeout(() => successMsg.remove(), 3000);
            }
        } else {
            try {
                const error = await response.json();
                alert('Error sending tax invoice: ' + (error.error || 'Unknown error'));
            } catch (jsonError) {
                const text = await response.text();
                alert('Error sending tax invoice: ' + (text || 'Unknown error'));
            }
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Revenue Filter Functions
let currentRevenuePeriod = 'all';

function toggleRevenueFilter() {
    const dropdown = document.getElementById('revenueFilterDropdown');
    const revenueCard = document.querySelector('.stat-card:last-child');
    
    if (dropdown.style.display === 'none') {
        // Position dropdown below revenue card
        const rect = revenueCard.getBoundingClientRect();
        dropdown.style.top = (rect.bottom + 10) + 'px';
        dropdown.style.left = (rect.left + rect.width / 2) + 'px';
        dropdown.style.transform = 'translateX(-50%)';
        dropdown.style.display = 'block';
    } else {
        dropdown.style.display = 'none';
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('revenueFilterDropdown');
    const revenueCard = document.querySelector('.stat-card:last-child');
    if (dropdown && !revenueCard.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

async function setRevenuePeriod(period) {
    currentRevenuePeriod = period;
    const dropdown = document.getElementById('revenueFilterDropdown');
    dropdown.style.display = 'none';
    
    // Update label
    const labels = {
        'today': 'Today\'s Revenue',
        'yesterday': 'Yesterday\'s Revenue',
        'this_week': 'This Week\'s Revenue',
        'this_month': 'This Month\'s Revenue',
        'this_year': 'This Year\'s Revenue',
        'all': 'Total Revenue'
    };
    document.getElementById('revenue-period-label').textContent = labels[period] || 'Total Revenue';
    
    // Store filter for table display
    currentRevenuePeriodFilter = period === 'all' ? null : { period, customDate: null };
    
    // Fetch revenue for period
    await updateRevenue(period);
    
    // Re-render table with filter
    applySortAndRender();
}

async function updateRevenue(period, customDate = null) {
    try {
        let url = getApiUrl('/api/index.php?request=orders');
        
        const response = await fetch(url);
        const data = await response.json();
        
        // Handle both array and object with data property
        const allOrders = Array.isArray(data) ? data : (data.data || []);
        
        if (!allOrders || allOrders.length === 0) {
            document.getElementById('stat-revenue').textContent = '$0.00';
            return;
        }
        
        // Filter orders based on period
        const filteredOrders = filterOrdersByPeriod(allOrders, period, customDate);
        
        // Calculate revenue (exclude cancelled orders)
        let revenue = 0;
        filteredOrders.forEach(order => {
            if (order.status !== 'cancelled') {
                revenue += parseFloat(order.total_amount || order.total || 0);
            }
        });
        
        document.getElementById('stat-revenue').textContent = '$' + revenue.toFixed(2);
    } catch (error) {
        console.error('Error updating revenue:', error);
        document.getElementById('stat-revenue').textContent = '$0.00';
    }
}

function filterOrdersByPeriod(orders, period, customDate = null) {
    if (period === 'all') {
        return orders;
    }
    
    const now = new Date();
    return orders.filter(order => {
        const orderDate = new Date(order.created_at || order.order_date);
                
                switch(period) {
                    case 'today':
                        return orderDate.toDateString() === now.toDateString();
                    
                    case 'yesterday':
                        const yesterday = new Date(now);
                        yesterday.setDate(yesterday.getDate() - 1);
                        return orderDate.toDateString() === yesterday.toDateString();
                    
                    case 'this_week':
                        const weekStart = new Date(now);
                        weekStart.setDate(weekStart.getDate() - weekStart.getDay());
                        weekStart.setHours(0, 0, 0, 0);
                        return orderDate >= weekStart && orderDate <= now;
                    
                    case 'this_month':
                        return orderDate.getMonth() === now.getMonth() && 
                               orderDate.getFullYear() === now.getFullYear();
                    
                    case 'this_year':
                        return orderDate.getFullYear() === now.getFullYear();
                    
                    case 'custom':
                        if (customDate) {
                            if (typeof customDate === 'object' && customDate.from && customDate.to) {
                                // Date range
                                const fromDate = new Date(customDate.from);
                                const toDate = new Date(customDate.to);
                                fromDate.setHours(0, 0, 0, 0);
                                toDate.setHours(23, 59, 59, 999);
                                return orderDate >= fromDate && orderDate <= toDate;
                            } else {
                                // Single date (backward compatibility)
                                const targetDate = new Date(customDate);
                                return orderDate.toDateString() === targetDate.toDateString();
                            }
                        }
                        return true;
                    
                default:
                    return true;
        }
    });
}

function showDatePicker() {
    const modal = document.getElementById('dateRangeModal');
    const backdrop = document.getElementById('dateRangeBackdrop');
    const dropdown = document.getElementById('revenueFilterDropdown');
    
    // Set default dates (today)
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dateFrom').value = today;
    document.getElementById('dateTo').value = today;
    
    dropdown.style.display = 'none';
    backdrop.style.display = 'block';
    modal.style.display = 'block';
}

function closeDateRangeModal() {
    document.getElementById('dateRangeModal').style.display = 'none';
    document.getElementById('dateRangeBackdrop').style.display = 'none';
}

function applyDateRange() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    if (!dateFrom || !dateTo) {
        showError('Please select both from and to dates');
        return;
    }
    
    if (new Date(dateFrom) > new Date(dateTo)) {
        showError('From date must be before or equal to To date');
        return;
    }
    
    currentRevenuePeriod = 'custom';
    
    // Format dates as dd/mm/yy
    const formatDate = (dateStr) => {
        const d = new Date(dateStr);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = String(d.getFullYear()).slice(-2);
        return `${day}/${month}/${year}`;
    };
    
    // Update label
    if (dateFrom === dateTo) {
        document.getElementById('revenue-period-label').textContent = `Revenue on ${formatDate(dateFrom)}`;
    } else {
        document.getElementById('revenue-period-label').textContent = `Revenue: ${formatDate(dateFrom)} to ${formatDate(dateTo)}`;
    }
    
    // Store filter for table display
    currentRevenuePeriodFilter = { period: 'custom', customDate: { from: dateFrom, to: dateTo } };
    
    updateRevenue('custom', { from: dateFrom, to: dateTo });
    closeDateRangeModal();
    
    // Re-render table with filter
    applySortAndRender();
}

function setCustomDate(date) {
    // Deprecated - keeping for compatibility
    if (date) {
        currentRevenuePeriod = 'custom';
        document.getElementById('revenue-period-label').textContent = `Revenue on ${date}`;
        updateRevenue('custom', date);
    }
}

// Initialize
loadOrders();
</script>
        </main>
    </div>
    <?php include '../components/toast-notification.php'; ?>
    <script src="../assets/js/date-formatter.js"></script>
</body>
</html>
