<?php
require_once '../frontend/config.php';

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    header('Location: ' . $protocol . $host . BASE_PATH . 'admin-login');
    exit;
}

$pageTitle = 'Contact Messages';
include __DIR__ . '/../components/admin-header.php';
?>

<style>
    .page-header {
        background: white;
        padding: 25px 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }
    
    .page-header h1 {
        margin: 0;
        color: #2f3192;
        font-size: 28px;
    }
    
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        text-align: center;
    }
    
    .stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 36px;
        color: #2f3192;
    }
    
    .stat-card p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }
    
    .content-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .filter-bar {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .filter-bar select,
    .filter-bar input {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th {
        background: #f5f5f5;
        color: #333;
        padding: 15px;
        text-align: left;
        font-weight: 600;
    }
    
    td {
        padding: 15px;
        border-bottom: 1px solid #eee;
    }
    
    tr:hover {
        background: #f8f9fa;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-new { background: #e3f2fd; color: #1976d2; }
    .status-replied { background: #f3e5f5; color: #7b1fa2; }
    .status-resolved { background: #e8f5e9; color: #388e3c; }
    
    .action-btns {
        display: flex;
        gap: 5px;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-view { background: #2196f3; color: #fff; }
    .btn-edit { background: #ff9800; color: #fff; }
    .btn-delete { background: #f44336; color: #fff; }
    .btn-email { background: #4caf50; color: #fff; }
    .btn-sm:hover { opacity: 0.8; }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    .modal.active {
        display: flex;
    }
    
    .modal-content {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        max-width: 800px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #eee;
    }
    
    .modal-header h2 {
        margin: 0;
        color: #2f3192;
    }
    
    .close-modal {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }
    
    .detail-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .detail-label {
        font-weight: 600;
        color: #666;
    }
    
    .message-box {
        background: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
        margin: 15px 0;
        border-left: 4px solid #2f3192;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }
    
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #eee;
    }
    
    .btn {
        padding: 10px 24px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-primary { background: #2f3192; color: #fff; }
    .btn-secondary { background: #666; color: #fff; }
    .btn-success { background: #4caf50; color: #fff; }
    .btn:hover { opacity: 0.9; }
    
    .loading {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        color: #ddd;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .stats-cards {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-bar label {
            width: 100%;
        }
        
        .filter-bar select,
        .filter-bar input {
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .stats-cards {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <h1>Contact Messages</h1>
</div>

<div class="stats-cards">
    <div class="stat-card">
        <h3 id="stat-new">0</h3>
        <p>New Messages</p>
    </div>
    <div class="stat-card">
        <h3 id="stat-replied">0</h3>
        <p>Replied</p>
    </div>
    <div class="stat-card">
        <h3 id="stat-resolved">0</h3>
        <p>Resolved</p>
    </div>
</div>

<div class="content-card">
    <div class="filter-bar">
        <label>
            <strong>Status:</strong>
            <select id="statusFilter">
                <option value="all">All</option>
                <option value="new">New</option>
                <option value="replied">Replied</option>
                <option value="resolved">Resolved</option>
            </select>
        </label>
        <label>
            <strong>Search:</strong>
            <input type="text" id="searchInput" placeholder="Name, email, subject...">
        </label>
    </div>
    
    <div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="messagesBody">
            <tr>
                <td colspan="8" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading messages...
                </td>
            </tr>
        </tbody>
    </table>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-eye"></i> Message Details</h2>
            <button class="close-modal" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div id="viewModalBody"></div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Update Status</h2>
            <button class="close-modal" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <form id="statusForm">
            <input type="hidden" id="statusId">
            
            <div class="form-group">
                <label>Status</label>
                <select id="statusValue" required>
                    <option value="new">New</option>
                    <option value="replied">Replied</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let allMessages = [];
    
    document.addEventListener('DOMContentLoaded', function() {
        loadMessages();
        
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('searchInput').addEventListener('input', applyFilters);
        
        document.getElementById('statusForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            await updateStatus();
        });
    });
    
    async function loadMessages() {
        try {
            const result = await apiFetch(getApiUrl('/api/contact/list.php'));
            
            if (result.success) {
                allMessages = result.data;
                updateStats();
                displayMessages(allMessages);
            }
        } catch (error) {
            console.error('Failed to load messages:', error);
            alert('Failed to load contact messages');
        }
    }
    
    function updateStats() {
        const stats = {
            new: 0,
            replied: 0,
            resolved: 0
        };
        
        allMessages.forEach(msg => {
            if (stats.hasOwnProperty(msg.status)) {
                stats[msg.status]++;
            }
        });
        
        document.getElementById('stat-new').textContent = stats.new;
        document.getElementById('stat-replied').textContent = stats.replied;
        document.getElementById('stat-resolved').textContent = stats.resolved;
    }
    
    function displayMessages(messages) {
        const tbody = document.getElementById('messagesBody');
        
        if (messages.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No contact messages found</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = messages.map(msg => `
            <tr>
                <td data-label="ID">${msg.id}</td>
                <td data-label="Date">${formatDate(msg.created_at)}</td>
                <td data-label="Name">${escapeHtml(msg.name)}</td>
                <td data-label="Email"><a href="mailto:${msg.email}">${escapeHtml(msg.email)}</a></td>
                <td data-label="Subject">${escapeHtml(msg.subject || 'N/A')}</td>
                <td data-label="Message">${truncate(escapeHtml(msg.message), 50)}</td>
                <td data-label="Status"><span class="status-badge status-${msg.status}">${capitalize(msg.status)}</span></td>
                <td data-label="Actions">
                    <div class="action-btns">
                        <button class="btn-sm btn-view" onclick="viewMessage(${msg.id})" title="View details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-sm btn-email" onclick="replyEmail('${msg.email}')" title="Reply via email">
                            <i class="fas fa-reply"></i>
                        </button>
                        <button class="btn-sm btn-edit" onclick="updateMessageStatus(${msg.id})" title="Update status">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-sm btn-delete" onclick="deleteMessage(${msg.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    function applyFilters() {
        const statusFilter = document.getElementById('statusFilter').value;
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        
        let filtered = allMessages;
        
        if (statusFilter !== 'all') {
            filtered = filtered.filter(m => m.status === statusFilter);
        }
        
        if (searchTerm) {
            filtered = filtered.filter(m => 
                m.name.toLowerCase().includes(searchTerm) ||
                m.email.toLowerCase().includes(searchTerm) ||
                (m.subject && m.subject.toLowerCase().includes(searchTerm)) ||
                m.message.toLowerCase().includes(searchTerm)
            );
        }
        
        displayMessages(filtered);
    }
    
    function viewMessage(id) {
        const msg = allMessages.find(m => m.id === id);
        if (!msg) return;
        
        document.getElementById('viewModalBody').innerHTML = `
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div><span class="status-badge status-${msg.status}">${capitalize(msg.status)}</span></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Name:</div>
                <div>${escapeHtml(msg.name)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Email:</div>
                <div><a href="mailto:${msg.email}">${escapeHtml(msg.email)}</a></div>
            </div>
            ${msg.phone ? `
            <div class="detail-row">
                <div class="detail-label">Phone:</div>
                <div><a href="tel:${msg.phone}">${escapeHtml(msg.phone)}</a></div>
            </div>` : ''}
            ${msg.subject ? `
            <div class="detail-row">
                <div class="detail-label">Subject:</div>
                <div>${escapeHtml(msg.subject)}</div>
            </div>` : ''}
            <div class="detail-row">
                <div class="detail-label">Message:</div>
                <div class="message-box">${escapeHtml(msg.message)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Received:</div>
                <div>${formatDate(msg.created_at)}</div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-success" onclick="replyEmail('${msg.email}')">
                    <i class="fas fa-reply"></i> Reply via Email
                </button>
            </div>
        `;
        
        openModal('viewModal');
    }
    
    function updateMessageStatus(id) {
        const msg = allMessages.find(m => m.id === id);
        if (!msg) return;
        
        document.getElementById('statusId').value = msg.id;
        document.getElementById('statusValue').value = msg.status;
        
        openModal('statusModal');
    }
    
    async function updateStatus() {
        const id = document.getElementById('statusId').value;
        const status = document.getElementById('statusValue').value;
        
        try {
            const result = await apiFetch(getApiUrl('/api/contact/update.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: parseInt(id),
                    status
                })
            });
            
            if (result.success) {
                closeModal('statusModal');
                await loadMessages();
                showNotification('Status updated successfully', 'success');
            } else {
                showNotification(result.error || 'Failed to update status', 'error');
            }
        } catch (error) {
            console.error('Update error:', error);
            showNotification('Failed to update status', 'error');
        }
    }
    
    async function deleteMessage(id) {
        if (!confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
            return;
        }
        
        try {
            const result = await apiFetch(getApiUrl('/api/contact/delete.php'), {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            
            if (result.success) {
                await loadMessages();
                showNotification('Message deleted successfully', 'success');
            } else {
                showNotification(result.error || 'Failed to delete message', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('Failed to delete message', 'error');
        }
    }
    
    function replyEmail(email) {
        window.location.href = `mailto:${email}`;
    }
    
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-NZ', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function truncate(text, length) {
        if (text.length <= length) return text;
        return text.substring(0, length) + '...';
    }
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            background: ${type === 'success' ? '#d4edda' : '#f8d7da'};
            color: ${type === 'success' ? '#155724' : '#721c24'};
            border: 1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'};
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            font-weight: 600;
            animation: slideIn 0.3s ease;
        `;
        
        const icon = type === 'success' ? '✓' : '✗';
        notification.innerHTML = `<span style="font-size: 18px; margin-right: 8px;">${icon}</span>${message}`;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
</script>

<?php include __DIR__ . '/../components/admin-footer.php'; ?>
