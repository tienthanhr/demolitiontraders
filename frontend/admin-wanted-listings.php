<?php
$pageTitle = 'Wanted Listings';
include __DIR__ . '/components/admin-header.php';
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
    
    .status-active { background: #e8f5e9; color: #388e3c; }
    .status-matched { background: #e3f2fd; color: #1976d2; }
    .status-cancelled { background: #ffebee; color: #d32f2f; }
    .status-expired { background: #f5f5f5; color: #666; }
    
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
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }
    
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .form-group textarea {
        min-height: 100px;
        resize: vertical;
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
</style>

<div class="page-header">
    <h1><i class="fas fa-star"></i> Wanted Listings</h1>
</div>

<div class="stats-cards">
    <div class="stat-card">
        <h3 id="stat-active">0</h3>
        <p>Active Listings</p>
    </div>
    <div class="stat-card">
        <h3 id="stat-matched">0</h3>
        <p>Matched</p>
    </div>
    <div class="stat-card">
        <h3 id="stat-total">0</h3>
        <p>Total Listings</p>
    </div>
</div>

<div class="content-card">
    <div class="filter-bar">
        <label>
            <strong>Status:</strong>
            <select id="statusFilter">
                <option value="all">All</option>
                <option value="active">Active</option>
                <option value="matched">Matched</option>
                <option value="cancelled">Cancelled</option>
                <option value="expired">Expired</option>
            </select>
        </label>
        <label>
            <strong>Search:</strong>
            <input type="text" id="searchInput" placeholder="Name, email, description...">
        </label>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Category</th>
                <th>Description</th>
                <th>Matches</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="listingsBody">
            <tr>
                <td colspan="9" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading listings...
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-eye"></i> Listing Details</h2>
            <button class="close-modal" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div id="viewModalBody"></div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Update Listing</h2>
            <button class="close-modal" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form id="editForm">
            <input type="hidden" id="editId">
            
            <div class="form-group">
                <label>Status</label>
                <select id="editStatus" required>
                    <option value="active">Active</option>
                    <option value="matched">Matched</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="editNotify"> 
                    Enable email notifications
                </label>
            </div>
            
            <div class="form-group">
                <label>Admin Notes (Internal)</label>
                <textarea id="editNotes" placeholder="Add notes about this listing..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let allListings = [];
    
    document.addEventListener('DOMContentLoaded', function() {
        loadListings();
        
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('searchInput').addEventListener('input', applyFilters);
        
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            await updateListing();
        });
    });
    
    async function loadListings() {
        try {
            const result = await apiFetch(getApiUrl('/api/wanted/list.php'));
            
            if (result.success) {
                allListings = result.data;
                updateStats();
                displayListings(allListings);
            }
        } catch (error) {
            console.error('Failed to load listings:', error);
            alert('Failed to load wanted listings');
        }
    }
    
    function updateStats() {
        const stats = {
            active: 0,
            matched: 0,
            total: allListings.length
        };
        
        allListings.forEach(listing => {
            if (listing.status === 'active') stats.active++;
            if (listing.status === 'matched') stats.matched++;
        });
        
        document.getElementById('stat-active').textContent = stats.active;
        document.getElementById('stat-matched').textContent = stats.matched;
        document.getElementById('stat-total').textContent = stats.total;
    }
    
    function displayListings(listings) {
        const tbody = document.getElementById('listingsBody');
        
        if (listings.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No wanted listings found</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = listings.map(listing => `
            <tr>
                <td>${listing.id}</td>
                <td>${formatDate(listing.created_at)}</td>
                <td>${escapeHtml(listing.username || 'Guest')}</td>
                <td>
                    <div>${escapeHtml(listing.email)}</div>
                    ${listing.phone ? `<small>${escapeHtml(listing.phone)}</small>` : ''}
                </td>
                <td>${escapeHtml(listing.category || 'N/A')}</td>
                <td>${truncate(escapeHtml(listing.description), 50)}</td>
                <td><span class="status-badge status-active">${listing.match_count || 0}</span></td>
                <td><span class="status-badge status-${listing.status}">${capitalize(listing.status)}</span></td>
                <td>
                    <div class="action-btns">
                        <button class="btn-sm btn-view" onclick="viewListing(${listing.id})" title="View details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-sm btn-edit" onclick="editListing(${listing.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-sm btn-delete" onclick="deleteListing(${listing.id})" title="Delete">
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
        
        let filtered = allListings;
        
        if (statusFilter !== 'all') {
            filtered = filtered.filter(l => l.status === statusFilter);
        }
        
        if (searchTerm) {
            filtered = filtered.filter(l => 
                (l.name && l.name.toLowerCase().includes(searchTerm)) ||
                (l.email && l.email.toLowerCase().includes(searchTerm)) ||
                (l.description && l.description.toLowerCase().includes(searchTerm)) ||
                (l.category && l.category.toLowerCase().includes(searchTerm))
            );
        }
        
        displayListings(filtered);
    }
    
    function viewListing(id) {
        const listing = allListings.find(l => l.id === id);
        if (!listing) return;
        
        document.getElementById('viewModalBody').innerHTML = `
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div><span class="status-badge status-${listing.status}">${capitalize(listing.status)}</span></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Customer:</div>
                <div>${escapeHtml(listing.username || 'Guest')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Name:</div>
                <div>${escapeHtml(listing.name)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Email:</div>
                <div><a href="mailto:${listing.email}">${escapeHtml(listing.email)}</a></div>
            </div>
            ${listing.phone ? `
            <div class="detail-row">
                <div class="detail-label">Phone:</div>
                <div><a href="tel:${listing.phone}">${escapeHtml(listing.phone)}</a></div>
            </div>` : ''}
            <div class="detail-row">
                <div class="detail-label">Category:</div>
                <div>${escapeHtml(listing.category || 'N/A')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Quantity:</div>
                <div>${escapeHtml(listing.quantity || 'N/A')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Description:</div>
                <div>${escapeHtml(listing.description)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Notifications:</div>
                <div>${listing.notify_enabled ? '✓ Enabled' : '✗ Disabled'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Matches:</div>
                <div>${listing.match_count || 0} product(s)</div>
            </div>
            ${listing.notes ? `
            <div class="detail-row">
                <div class="detail-label">Admin Notes:</div>
                <div>${escapeHtml(listing.notes)}</div>
            </div>` : ''}
            <div class="detail-row">
                <div class="detail-label">Created:</div>
                <div>${formatDate(listing.created_at)}</div>
            </div>
        `;
        
        openModal('viewModal');
    }
    
    function editListing(id) {
        const listing = allListings.find(l => l.id === id);
        if (!listing) return;
        
        document.getElementById('editId').value = listing.id;
        document.getElementById('editStatus').value = listing.status;
        document.getElementById('editNotify').checked = listing.notify_enabled == 1;
        document.getElementById('editNotes').value = listing.notes || '';
        
        openModal('editModal');
    }
    
    async function updateListing() {
        const id = document.getElementById('editId').value;
        const status = document.getElementById('editStatus').value;
        const notifyEnabled = document.getElementById('editNotify').checked ? 1 : 0;
        const notes = document.getElementById('editNotes').value;
        
        try {
            const result = await apiFetch(getApiUrl('/api/wanted/update.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: parseInt(id),
                    status,
                    notify_enabled: notifyEnabled,
                    notes
                })
            });
            
            if (result.success) {
                closeModal('editModal');
                await loadListings();
                alert('✓ Listing updated successfully');
            } else {
                alert('✗ ' + (result.error || 'Failed to update listing'));
            }
        } catch (error) {
            console.error('Update error:', error);
            alert('✗ Failed to update listing');
        }
    }
    
    async function deleteListing(id) {
        if (!confirm('Are you sure you want to delete this listing? This action cannot be undone.')) {
            return;
        }
        
        try {
            const result = await apiFetch(getApiUrl('/api/wanted/delete.php'), {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            
            if (result.success) {
                await loadListings();
                alert('✓ Listing deleted successfully');
            } else {
                alert('✗ ' + (result.error || 'Failed to delete listing'));
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('✗ Failed to delete listing');
        }
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
            day: 'numeric' 
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
</script>

<?php include __DIR__ . '/components/admin-footer.php'; ?>
