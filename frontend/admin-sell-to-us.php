<?php
$pageTitle = 'Sell to Us Management';
$additionalCSS = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
<style>
    .page-header {
        background: white;
        padding: 25px 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-header h1 {
        margin: 0;
        color: #2f3192;
        font-size: 28px;
    }
    
    .view-toggle {
        display: flex;
        gap: 10px;
    }
    
    .view-toggle button {
        padding: 8px 16px;
        border: 2px solid #2f3192;
        background: white;
        color: #2f3192;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .view-toggle button.active {
        background: #2f3192;
        color: white;
    }
    
    .view-toggle button:hover {
        opacity: 0.8;
    }
    
    #calendarView {
        display: none;
    }
    
    #calendarView.active {
        display: block;
    }
    
    #tableView.active {
        display: block;
    }
    
    .fc-event {
        cursor: pointer;
    }
    
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
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
        margin-bottom: 30px;
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
    
    .filter-bar label {
        display: flex;
        align-items: center;
        gap: 8px;
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
        .status-reviewing { background: #fff3e0; color: #f57c00; }
        .status-contacted { background: #f3e5f5; color: #7b1fa2; }
        .status-purchased { background: #e8f5e9; color: #388e3c; }
        .status-declined { background: #ffebee; color: #d32f2f; }
        
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
        
        .btn-view {
            background: #2196f3;
            color: #fff;
        }
        
        .btn-edit {
            background: #ff9800;
            color: #fff;
        }
        
        .btn-delete {
            background: #f44336;
            color: #fff;
        }
        
        .btn-sm:hover {
            opacity: 0.8;
        }
        
        /* Modal Styles */
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
        
        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .photo-gallery img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid #eee;
            transition: all 0.3s;
        }
        
        .photo-gallery img:hover {
            border-color: #2f3192;
            transform: scale(1.05);
        }
        
        .photo-item {
            position: relative;
        }
        
        .item-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid #eee;
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
        .form-group input,
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
        
        .btn-primary {
            background: #2f3192;
            color: #fff;
        }
        
        .btn-secondary {
            background: #666;
            color: #fff;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
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
</style>';

include __DIR__ . '/components/admin-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Sell to Us Management</h1>
    <div class="view-toggle">
        <button id="btnTableView" class="active" onclick="switchView('table')">
            <i class="fas fa-table"></i> Table View
        </button>
        <button id="btnCalendarView" onclick="switchView('calendar')">
            <i class="fas fa-calendar"></i> Calendar View
        </button>
    </div>
</div>

<!-- Table View -->
<div id="tableView" class="active">
    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <h3 id="stat-new">0</h3>
            <p>New Submissions</p>
        </div>
        <div class="stat-card">
            <h3 id="stat-reviewing">0</h3>
            <p>Under Review</p>
        </div>
        <div class="stat-card">
            <h3 id="stat-contacted">0</h3>
            <p>Contacted</p>
        </div>
        <div class="stat-card">
            <h3 id="stat-purchased">0</h3>
            <p>Purchased</p>
        </div>
    </div>
    
    <!-- Content Card with Filter Bar -->
    <div class="content-card">
        <!-- Filter Bar -->
        <div class="filter-bar">
            <label>
                <strong>Status:</strong>
                <select id="statusFilter">
                    <option value="all">All</option>
                    <option value="new">New</option>
                    <option value="reviewing">Reviewing</option>
                    <option value="contacted">Contacted</option>
                    <option value="purchased">Purchased</option>
                    <option value="declined">Declined</option>
                </select>
            </label>
            <label>
                <strong>Type:</strong>
                <select id="typeFilter">
                    <option value="all">All</option>
                    <option value="pickup">Pickup</option>
                    <option value="delivery">Delivery</option>
                </select>
            </label>
            <label>
                <strong>Search:</strong>
                <input type="text" id="searchInput" placeholder="Name, email, item...">
            </label>
            <div style="margin-left: auto; display: flex; gap: 10px;">
                <button class="btn-sm btn-delete" onclick="bulkDelete()" id="bulkDeleteBtn" style="display:none;">
                    <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>

        <!-- Undo notification -->
        <div id="undoNotification" style="display: none; background: #fff3cd; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #ffc107;">
            <strong>Note:</strong> <span id="undoMessage"></span>
            <button class="btn-sm" onclick="undoDelete()" style="background: #28a745; color: white; margin-left: 10px;">
                <i class="fas fa-undo"></i> Undo
            </button>
            <button class="btn-sm" onclick="closeUndo()" style="background: #6c757d; color: white;">
                <i class="fas fa-times"></i> Dismiss
            </button>
        </div>

        <!-- Submissions Table -->
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                    <th>ID</th>
                    <th>Photo</th>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Pickup Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="submissionsBody">
                <tr>
                    <td colspan="11" class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Loading submissions...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Calendar View -->
<div id="calendarView" class="content-card">
    <div id="calendar"></div>
</div>
    
    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-eye"></i> Submission Details</h2>
                <button class="close-modal" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div id="viewModalBody"></div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Update Submission</h2>
                <button class="close-modal" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form id="editForm">
                <input type="hidden" id="editId">
                
                <div class="form-group">
                    <label>Status</label>
                    <select id="editStatus" required>
                        <option value="new">New</option>
                        <option value="reviewing">Reviewing</option>
                        <option value="contacted">Contacted</option>
                        <option value="purchased">Purchased</option>
                        <option value="declined">Declined</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Pickup Date</label>
                    <input type="date" id="editPickupDate">
                </div>
                
                <div class="form-group">
                    <label>Notes (Internal)</label>
                    <textarea id="editNotes" placeholder="Add notes about this submission..."></textarea>
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
    
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        let allSubmissions = [];
        let calendar;
        
        // Load submissions on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSubmissions();
            initCalendar();
            
            // Filter changes
            document.getElementById('statusFilter').addEventListener('change', applyFilters);
            document.getElementById('typeFilter').addEventListener('change', applyFilters);
            document.getElementById('searchInput').addEventListener('input', applyFilters);
            
            // Edit form submit
            document.getElementById('editForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                await updateSubmission();
            });
        });
        
        // Initialize calendar
        function initCalendar() {
            const calendarEl = document.getElementById('calendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                events: [],
                eventClick: function(info) {
                    const submissionId = parseInt(info.event.id);
                    viewSubmission(submissionId);
                },
                height: 'auto'
            });
            calendar.render();
        }
        
        // Switch between table and calendar views
        function switchView(view) {
            const tableView = document.getElementById('tableView');
            const calendarView = document.getElementById('calendarView');
            const btnTable = document.getElementById('btnTableView');
            const btnCalendar = document.getElementById('btnCalendarView');
            
            if (view === 'calendar') {
                tableView.classList.remove('active');
                calendarView.classList.add('active');
                btnTable.classList.remove('active');
                btnCalendar.classList.add('active');
                updateCalendar();
            } else {
                tableView.classList.add('active');
                calendarView.classList.remove('active');
                btnTable.classList.add('active');
                btnCalendar.classList.remove('active');
            }
        }
        
        // Update calendar with pickup dates
        function updateCalendar() {
            if (!calendar) return;
            
            const events = allSubmissions
                .filter(sub => sub.pickup_date && sub.pickup_delivery.toLowerCase().includes('pickup'))
                .map(sub => ({
                    id: sub.id,
                    title: `${sub.name} - ${sub.item_name}`,
                    start: sub.pickup_date,
                    backgroundColor: getStatusColor(sub.status),
                    borderColor: getStatusColor(sub.status),
                    extendedProps: {
                        submission: sub
                    }
                }));
            
            calendar.removeAllEvents();
            calendar.addEventSource(events);
        }
        
        // Get color by status
        function getStatusColor(status) {
            const colors = {
                'new': '#1976d2',
                'reviewing': '#f57c00',
                'contacted': '#7b1fa2',
                'purchased': '#388e3c',
                'declined': '#d32f2f'
            };
            return colors[status] || '#666';
        }
        
        // Load submissions from API
        async function loadSubmissions() {
            try {
                const result = await apiFetch(getApiUrl('/api/sell-to-us/list.php'));
                
                if (result.success) {
                    allSubmissions = result.data;
                    updateStats();
                    displaySubmissions(allSubmissions);
                    updateCalendar();
                }
            } catch (error) {
                console.error('Failed to load submissions:', error);
                showError('Failed to load submissions');
            }
        }
        
        // Update stats cards
        function updateStats() {
            const stats = {
                new: 0,
                reviewing: 0,
                contacted: 0,
                purchased: 0
            };
            
            allSubmissions.forEach(sub => {
                if (stats.hasOwnProperty(sub.status)) {
                    stats[sub.status]++;
                }
            });
            
            document.getElementById('stat-new').textContent = stats.new;
            document.getElementById('stat-reviewing').textContent = stats.reviewing;
            document.getElementById('stat-contacted').textContent = stats.contacted;
            document.getElementById('stat-purchased').textContent = stats.purchased;
        }
        
        // Display submissions in table
        function displaySubmissions(submissions) {
            const tbody = document.getElementById('submissionsBody');
            
            if (submissions.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="11" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No submissions found</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = submissions.map(sub => {
                const photoUrl = sub.photos && sub.photos.length > 0 ? sub.photos[0] : '';
                // Get base path from api-helper
                const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
                const basePath = isLocalhost ? '/demolitiontraders/' : '/';
                const fullPhotoUrl = photoUrl ? basePath + photoUrl : '';
                const thumbnail = fullPhotoUrl 
                    ? `<img src="${fullPhotoUrl}" class="item-thumbnail" onclick="viewSubmission(${sub.id})" title="Click to view details">`
                    : '<i class="fas fa-image" style="color: #ccc; font-size: 24px;"></i>';
                
                const isDeleted = sub.notes && sub.notes.includes('[DELETED]');
                
                return `
                <tr ${isDeleted ? 'style="opacity: 0.5; background: #fff3cd;"' : ''}>
                    <td><input type="checkbox" class="row-checkbox" value="${sub.id}" onchange="updateBulkActions()"></td>
                    <td>${sub.id}</td>
                    <td>${thumbnail}</td>
                    <td>${formatDate(sub.created_at)}</td>
                    <td>${escapeHtml(sub.name)}</td>
                    <td>
                        <div>${escapeHtml(sub.email)}</div>
                        <small>${escapeHtml(sub.phone)}</small>
                    </td>
                    <td>${escapeHtml(sub.item_name)}</td>
                    <td><span class="status-badge ${sub.pickup_delivery.toLowerCase().includes('pickup') ? 'status-contacted' : 'status-reviewing'}">${escapeHtml(sub.pickup_delivery)}</span></td>
                    <td>${sub.pickup_date ? formatDate(sub.pickup_date) : '<em>Not set</em>'}</td>
                    <td>
                        <span class="status-badge status-${sub.status}">${capitalize(sub.status)}</span>
                        ${isDeleted ? '<br><small style="color: #856404;">Deleted - can restore</small>' : ''}
                    </td>
                    <td>
                        <div class="action-btns">
                            ${isDeleted ? `
                                <button class="btn-sm" onclick="restoreSubmission(${sub.id})" title="Restore" style="background: #28a745; color: white;">
                                    <i class="fas fa-undo"></i>
                                </button>
                            ` : `
                                <button class="btn-sm btn-view" onclick="viewSubmission(${sub.id})" title="View details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-sm btn-edit" onclick="editSubmission(${sub.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-sm btn-delete" onclick="deleteSubmission(${sub.id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            `}
                        </div>
                    </td>
                </tr>
            `;
            }).join('');
        }
        
        // Apply all filters
        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            let filtered = allSubmissions;
            
            // Filter by status
            if (statusFilter !== 'all') {
                filtered = filtered.filter(sub => sub.status === statusFilter);
            }
            
            // Filter by type (pickup/delivery)
            if (typeFilter !== 'all') {
                filtered = filtered.filter(sub => 
                    sub.pickup_delivery.toLowerCase().includes(typeFilter)
                );
            }
            
            // Search filter
            if (searchTerm) {
                filtered = filtered.filter(sub => 
                    sub.name.toLowerCase().includes(searchTerm) ||
                    sub.email.toLowerCase().includes(searchTerm) ||
                    sub.item_name.toLowerCase().includes(searchTerm) ||
                    sub.phone.includes(searchTerm)
                );
            }
            
            displaySubmissions(filtered);
        }
        
        // Filter submissions (legacy support)
        function filterSubmissions(status) {
            document.getElementById('statusFilter').value = status;
            applyFilters();
        }
        
        // View submission details
        function viewSubmission(id) {
            const sub = allSubmissions.find(s => s.id === id);
            if (!sub) return;
            
            const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
            const basePath = isLocalhost ? '/demolitiontraders/' : '/';
            
            const photos = sub.photos && sub.photos.length > 0 
                ? `<div class="photo-gallery">
                    ${sub.photos.map(photo => {
                        const fullUrl = basePath + photo;
                        return `<img src="${fullUrl}" onclick="window.open('${fullUrl}', '_blank')">`;
                    }).join('')}
                   </div>`
                : '<em>No photos uploaded</em>';
            
            document.getElementById('viewModalBody').innerHTML = `
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div><span class="status-badge status-${sub.status}">${capitalize(sub.status)}</span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Name:</div>
                    <div>${escapeHtml(sub.name)}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div><a href="mailto:${sub.email}">${escapeHtml(sub.email)}</a></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Phone:</div>
                    <div><a href="tel:${sub.phone}">${escapeHtml(sub.phone)}</a></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Location:</div>
                    <div>${escapeHtml(sub.location || 'N/A')}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Item Name:</div>
                    <div>${escapeHtml(sub.item_name)}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Quantity:</div>
                    <div>${escapeHtml(sub.quantity)}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Pickup/Delivery:</div>
                    <div>${escapeHtml(sub.pickup_delivery)}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Pickup Date:</div>
                    <div>${sub.pickup_date ? formatDate(sub.pickup_date) : '<em>Not set</em>'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Description:</div>
                    <div>${escapeHtml(sub.description)}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Photos:</div>
                    <div>${photos}</div>
                </div>
                ${sub.notes ? `
                <div class="detail-row">
                    <div class="detail-label">Notes:</div>
                    <div>${escapeHtml(sub.notes)}</div>
                </div>
                ` : ''}
                <div class="detail-row">
                    <div class="detail-label">Submitted:</div>
                    <div>${formatDate(sub.created_at)}</div>
                </div>
            `;
            
            openModal('viewModal');
        }
        
        // Edit submission
        function editSubmission(id) {
            const sub = allSubmissions.find(s => s.id === id);
            if (!sub) return;
            
            document.getElementById('editId').value = sub.id;
            document.getElementById('editStatus').value = sub.status;
            document.getElementById('editPickupDate').value = sub.pickup_date || '';
            document.getElementById('editNotes').value = sub.notes || '';
            
            openModal('editModal');
        }
        
        // Update submission
        async function updateSubmission() {
            const id = document.getElementById('editId').value;
            const status = document.getElementById('editStatus').value;
            const pickupDate = document.getElementById('editPickupDate').value;
            const notes = document.getElementById('editNotes').value;
            
            try {
                const result = await apiFetch(getApiUrl('/api/sell-to-us/update.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: parseInt(id),
                        status,
                        pickup_date: pickupDate,
                        notes
                    })
                });
                
                if (result.success) {
                    closeModal('editModal');
                    await loadSubmissions();
                    showSuccess('Submission updated successfully');
                } else {
                    showError(result.error || 'Failed to update submission');
                }
            } catch (error) {
                console.error('Update error:', error);
                showError('Failed to update submission');
            }
        }
        
        // Delete submission (soft delete)
        async function deleteSubmission(id) {
            if (!confirm('Are you sure you want to delete this submission? You can restore it later.')) {
                return;
            }
            
            try {
                const result = await apiFetch(getApiUrl('/api/sell-to-us/soft-delete.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                
                if (result.success) {
                    window.lastDeletedIds = [id];
                    await loadSubmissions();
                    showUndoNotification('1 submission deleted. You can undo this action.');
                } else {
                    showError(result.error || 'Failed to delete submission');
                }
            } catch (error) {
                console.error('Delete error:', error);
                showError('Failed to delete submission');
            }
        }
        
        // Restore submission
        async function restoreSubmission(id) {
            try {
                const result = await apiFetch(getApiUrl('/api/sell-to-us/restore.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                
                if (result.success) {
                    await loadSubmissions();
                    showSuccess('Submission restored successfully');
                } else {
                    showError(result.error || 'Failed to restore submission');
                }
            } catch (error) {
                console.error('Restore error:', error);
                showError('Failed to restore submission');
            }
        }
        
        // Bulk delete
        async function bulkDelete() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => parseInt(cb.value));
            
            if (ids.length === 0) return;
            
            if (!confirm(`Delete ${ids.length} submission(s)? You can restore them later.`)) {
                return;
            }
            
            try {
                const result = await apiFetch(getApiUrl('/api/sell-to-us/bulk-delete.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids })
                });
                
                if (result.success) {
                    window.lastDeletedIds = ids;
                    document.getElementById('selectAll').checked = false;
                    await loadSubmissions();
                    showUndoNotification(`${ids.length} submission(s) deleted. You can undo this action.`);
                } else {
                    showError(result.error || 'Failed to delete submissions');
                }
            } catch (error) {
                console.error('Bulk delete error:', error);
                showError('Failed to delete submissions');
            }
        }
        
        // Undo last delete
        async function undoDelete() {
            if (!window.lastDeletedIds || window.lastDeletedIds.length === 0) return;
            
            try {
                for (const id of window.lastDeletedIds) {
                    await apiFetch(getApiUrl('/api/sell-to-us/restore.php'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });
                }
                
                window.lastDeletedIds = [];
                closeUndo();
                await loadSubmissions();
                showSuccess('Delete action undone successfully');
            } catch (error) {
                console.error('Undo error:', error);
                showError('Failed to undo delete');
            }
        }
        
        // Show undo notification
        function showUndoNotification(message) {
            document.getElementById('undoMessage').textContent = message;
            document.getElementById('undoNotification').style.display = 'block';
        }
        
        // Close undo notification
        function closeUndo() {
            document.getElementById('undoNotification').style.display = 'none';
            window.lastDeletedIds = [];
        }
        
        // Toggle select all
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkActions();
        }
        
        // Update bulk action button visibility
        function updateBulkActions() {
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = checkedCount;
            document.getElementById('bulkDeleteBtn').style.display = checkedCount > 0 ? 'block' : 'none';
        }
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Utility functions
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
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showSuccess(message) {
            alert('✓ ' + message);
        }
        
        function showError(message) {
            alert('✗ ' + message);
        }
    </script>

<?php include __DIR__ . '/components/admin-footer.php'; ?>
