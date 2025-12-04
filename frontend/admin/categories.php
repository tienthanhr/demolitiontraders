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
    <title>Categories Management - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    <link rel="stylesheet" href="admin/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/api-helper.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <?php include 'topbar.php'; ?>

<!-- Categories Management Content -->
<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">All Categories</h2>
        <button class="btn btn-primary" onclick="openCategoryModal()">
            <i class="fas fa-plus"></i> Add New Category
        </button>
    </div>

    <!-- Info Notice -->
    <div style="background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px 20px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-info-circle" style="color: #0c5460; font-size: 20px;"></i>
        <div style="flex: 1;">
            <strong style="color: #0c5460; font-size: 15px;">ℹ️ Undo Available:</strong>
            <p style="margin: 5px 0 0 0; color: #0c5460; font-size: 14px;">Deleted categories can be <strong>restored within 10 seconds</strong> using the Undo button.</p>
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="search-categories" placeholder="Search categories..." onkeyup="searchCategories()">
        <select id="sort-by" class="form-control" style="max-width: 200px;" onchange="sortCategories()">
            <option value="">Sort by...</option>
            <option value="id-asc">ID (Ascending)</option>
            <option value="id-desc">ID (Descending)</option>
            <option value="name-asc">Name (A-Z)</option>
            <option value="name-desc">Name (Z-A)</option>
            <option value="count-asc">Products (Low to High)</option>
            <option value="count-desc">Products (High to Low)</option>
        </select>
    </div>

    <!-- Tabs for Categories / Organize -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e0e0e0; padding: 0 0 15px 0;">
        <button class="tab-btn active" onclick="switchTab('manage')" style="padding: 10px 20px; border: none; background: none; font-weight: 600; color: #333; border-bottom: 3px solid #007bff; cursor: pointer; transition: all 0.2s;">
            <i class="fas fa-list"></i> Manage Categories
        </button>
        <button class="tab-btn" onclick="switchTab('organize')" style="padding: 10px 20px; border: none; background: none; font-weight: 600; color: #666; border-bottom: 3px solid transparent; cursor: pointer; transition: all 0.2s;">
            <i class="fas fa-arrows-alt"></i> Organize (Drag & Drop)
        </button>
    </div>

    <!-- Bulk Actions Bar -->
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

    <!-- Manage Tab -->
    <div id="manage-tab">
        <!-- Search box (only shown in manage tab) -->
        <div style="display: none;" id="manage-search-box" class="search-box">
            <input type="text" id="search-categories" placeholder="Search categories..." onkeyup="searchCategories()">
        </div>

    <div class="table-container">
        <table id="categories-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
                    </th>
                    <th onclick="sortTable('id')" style="cursor: pointer;" title="Click to sort">
                        ID <i class="fas fa-sort" style="opacity: 0.3;"></i>
                    </th>
                    <th onclick="sortTable('name')" style="cursor: pointer;" title="Click to sort">
                        Name <i class="fas fa-sort" style="opacity: 0.3;"></i>
                    </th>
                    <th>Slug</th>
                    <th onclick="sortTable('count')" style="cursor: pointer;" title="Click to sort">
                        Products Count <i class="fas fa-sort" style="opacity: 0.3;"></i>
                    </th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="categories-tbody">
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px;">
                        <div class="spinner"></div>
                        <p>Loading categories...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>

    <!-- Organize Tab -->
    <div id="organize-tab" style="display: none;">
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <p style="color: #666; margin: 0;"><i class="fas fa-info-circle"></i> <strong>Drag categories to reorder</strong> • Click eye icon to show/hide • Same layout as the header menu</p>
        </div>

        <!-- Header Preview Section -->
        <div style="background: white; padding: 40px; border-radius: 8px; border: 2px solid #e0e0e0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h4 style="margin: 0; color: #333;"><i class="fas fa-desktop"></i> Header Menu Preview</h4>
                <button class="btn btn-primary btn-sm" onclick="showAddCategoryModal()">
                    <i class="fas fa-plus"></i> Add New Category
                </button>
            </div>

            <!-- Desktop Header Preview (Horizontal Dropdown Style) -->
            <div style="background: #2c3e50; padding: 12px 15px; border-radius: 8px; margin-bottom: 30px; overflow: visible; position: relative; z-index: 2000;">
                <div style="display: flex; gap: 6px; align-items: center; white-space: nowrap;" id="header-preview-container">
                    <!-- Categories shown horizontally like in actual header -->
                </div>
            </div>

            <!-- Drag to Reorder Section (Organized List) -->
            <h4 style="margin: 20px 0 20px 0; color: #333; padding-top: 20px; border-top: 2px solid #e0e0e0; position: relative; z-index: auto;">
                <i class="fas fa-grip-vertical"></i> Reorder Items
            </h4>
            <div id="organize-list-container" style="display: flex; flex-direction: column; gap: 12px; position: relative; z-index: auto;">
                <!-- Draggable items for reordering -->
            </div>
        </div>

        <!-- Hidden Categories Section (moved here) -->
        <div id="hidden-section" style="background: white; padding: 30px; border-radius: 8px; border: 2px solid #f0f0f0; margin-top: 30px; display: none;">
            <h4 style="margin: 0 0 20px 0; color: #999;"><i class="fas fa-eye-slash"></i> Hidden Categories (Not showing in header)</h4>
            <div id="hidden-categories-container" style="display: flex; flex-wrap: wrap; gap: 12px;">
                <!-- Hidden categories appear here -->
            </div>
        </div>

        <!-- Save Section -->
        <div style="position: sticky; bottom: 0; background: white; padding: 20px; border-top: 2px solid #e0e0e0; display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
            <div style="flex: 1;" id="changes-indicator" class="hidden">
                <span style="color: #ff6b6b; font-weight: 500;"><i class="fas fa-exclamation-circle"></i> Changes made</span>
            </div>
            <button class="btn btn-secondary" onclick="location.reload()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="btn btn-primary" onclick="saveOrder()" id="save-btn">
                <i class="fas fa-save"></i> Save Order
            </button>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal" id="category-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="category-modal-title">Add New Category</h3>
            <button class="close-modal" onclick="closeCategoryModal()">&times;</button>
        </div>
        <form id="category-form" onsubmit="saveCategory(event)">
            <input type="hidden" id="category-id">
            
            <div class="form-group" id="custom-id-group">
                <label class="form-label">Category ID</label>
                <input type="number" class="form-control" id="category-custom-id" placeholder="Leave empty for auto-increment">
                <small style="color: #6c757d;">Specify a custom ID or leave empty to auto-generate</small>
            </div>

            <div class="form-group">
                <label class="form-label">Category Name *</label>
                <input type="text" class="form-control" id="category-name" required>
            </div>

            <div class="form-group">
                <label class="form-label">Slug</label>
                <input type="text" class="form-control" id="category-slug" placeholder="Auto-generated if empty">
                <small style="color: #6c757d;">Leave empty to auto-generate from name</small>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-control" id="category-description"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Status *</label>
                <select class="form-control" id="category-status" required>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Category
                </button>
                <button type="button" class="btn btn-danger" onclick="closeCategoryModal()" style="flex: 1;">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let categoriesData = [];
let productCounts = {};
let currentSort = { column: '', direction: 'asc' };
let currentOrder = [];
let originalOrder = [];
let visibilityMap = {};
let parentMap = {};
let expandedCategories = {}; // Track which categories are expanded

// Load categories
async function loadCategories() {
    const tbody = document.getElementById('categories-tbody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;"><div class="spinner"></div><p>Loading categories...</p></td></tr>';

    try {
        const data = await apiFetch(getApiUrl('/api/index.php?request=categories'));
        categoriesData = data.data || data;

        if (!categoriesData || categoriesData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;">No categories found</td></tr>';
            return;
        }

        // Get product counts for each category
        const productsData = await apiFetch(getApiUrl('/api/index.php?request=products&per_page=1000'));
        const products = productsData.data || [];
        
        productCounts = {};
        products.forEach(p => {
            productCounts[p.category_id] = (productCounts[p.category_id] || 0) + 1;
        });

        renderCategories();
        
        // If organize tab is open, reload it too
        if (document.getElementById('organize-tab').style.display !== 'none') {
            loadOrganizeView();
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: red;">Error loading categories</td></tr>';
    }
}

// Render categories to table
function renderCategories() {
    const tbody = document.getElementById('categories-tbody');
    tbody.innerHTML = categoriesData.map(category => `
        <tr data-id="${category.id}" data-name="${category.name.toLowerCase()}" data-count="${productCounts[category.id] || 0}">
            <td>
                <input type="checkbox" class="category-checkbox" value="${category.id}" onchange="updateBulkActions()">
            </td>
            <td data-label="ID"><strong>${category.id}</strong></td>
            <td data-label="Name">${category.name}</td>
            <td data-label="Slug"><code>${category.slug}</code></td>
            <td data-label="Products">${productCounts[category.id] || 0} products</td>
            <td data-label="Status">
                <span class="badge badge-${category.is_active == 1 ? 'active' : 'inactive'}">
                    ${category.is_active == 1 ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td data-label="Actions">
                <div class="action-btns">
                    <button class="btn btn-warning btn-sm" onclick="editCategory(${category.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteCategory(${category.id}, '${category.name.replace(/'/g, "\\'")}')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    updateBulkActions();
}

// Sort categories by dropdown
function sortCategories() {
    const sortValue = document.getElementById('sort-by').value;
    if (!sortValue) return;

    const [column, direction] = sortValue.split('-');
    currentSort = { column, direction };

    const sorted = [...categoriesData].sort((a, b) => {
        let aVal, bVal;

        if (column === 'id') {
            aVal = parseInt(a.id);
            bVal = parseInt(b.id);
        } else if (column === 'name') {
            aVal = a.name.toLowerCase();
            bVal = b.name.toLowerCase();
        } else if (column === 'count') {
            aVal = productCounts[a.id] || 0;
            bVal = productCounts[b.id] || 0;
        }

        if (direction === 'asc') {
            return aVal > bVal ? 1 : -1;
        } else {
            return aVal < bVal ? 1 : -1;
        }
    });

    categoriesData = sorted;
    renderCategories();
}

// Sort table by clicking column header
function sortTable(column) {
    // Toggle direction if same column
    if (currentSort.column === column) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.column = column;
        currentSort.direction = 'asc';
    }

    const sorted = [...categoriesData].sort((a, b) => {
        let aVal, bVal;

        if (column === 'id') {
            aVal = parseInt(a.id);
            bVal = parseInt(b.id);
        } else if (column === 'name') {
            aVal = a.name.toLowerCase();
            bVal = b.name.toLowerCase();
        } else if (column === 'count') {
            aVal = productCounts[a.id] || 0;
            bVal = productCounts[b.id] || 0;
        }

        if (currentSort.direction === 'asc') {
            return aVal > bVal ? 1 : -1;
        } else {
            return aVal < bVal ? 1 : -1;
        }
    });

    categoriesData = sorted;
    renderCategories();

    // Update dropdown to match
    const sortValue = `${column}-${currentSort.direction}`;
    document.getElementById('sort-by').value = sortValue;

    // Update sort icons
    document.querySelectorAll('th i.fa-sort, th i.fa-sort-up, th i.fa-sort-down').forEach(icon => {
        icon.className = 'fas fa-sort';
        icon.style.opacity = '0.3';
    });

    const header = event.target.closest('th');
    const icon = header.querySelector('i');
    if (icon) {
        icon.className = currentSort.direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
        icon.style.opacity = '1';
    }
}

// Search categories
let searchTimeout;
function searchCategories() {
    const search = document.getElementById('search-categories').value.toLowerCase();
    const rows = document.querySelectorAll('#categories-tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
}

// Open modal
function openCategoryModal() {
    document.getElementById('category-modal-title').textContent = 'Add New Category';
    document.getElementById('category-form').reset();
    document.getElementById('category-id').value = '';
    document.getElementById('category-custom-id').value = '';
    document.getElementById('custom-id-group').style.display = 'block'; // Show ID field for new category
    document.getElementById('category-modal').classList.add('active');
}

// Close modal
function closeCategoryModal() {
    document.getElementById('category-modal').classList.remove('active');
}

// Edit category
async function editCategory(id) {
    try {
        const apiPath = `/api/index.php?request=categories/${id}`;
        const category = await apiFetch(getApiUrl(apiPath));

        document.getElementById('category-modal-title').textContent = 'Edit Category';
        document.getElementById('category-id').value = category.id;
        document.getElementById('category-custom-id').value = category.id;
        document.getElementById('custom-id-group').style.display = 'none'; // Hide ID field when editing
        document.getElementById('category-name').value = category.name;
        document.getElementById('category-slug').value = category.slug;
        document.getElementById('category-description').value = category.description || '';
        document.getElementById('category-status').value = category.is_active;

        document.getElementById('category-modal').classList.add('active');
    } catch (error) {
        alert('Error loading category: ' + error.message);
    }
}

// Save category
async function saveCategory(event) {
    event.preventDefault();

    const id = document.getElementById('category-id').value;
    const customId = document.getElementById('category-custom-id').value;
    const data = {
        name: document.getElementById('category-name').value,
        slug: document.getElementById('category-slug').value || null,
        description: document.getElementById('category-description').value,
        is_active: document.getElementById('category-status').value
    };

    // Include custom ID only for new categories
    if (!id && customId) {
        data.id = parseInt(customId);
    }

    try {
        const apiPath = id ? `/api/index.php?request=categories/${id}` : '/api/index.php?request=categories';
        const url = getApiUrl(apiPath);
        
        const response = await fetch(url, {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            alert(id ? 'Category updated successfully!' : 'Category created successfully!');
            closeCategoryModal();
            loadCategories();
        } else {
            const error = await response.json();
            alert('Error: ' + (error.error || 'Failed to save category'));
        }
    } catch (error) {
        alert('Error saving category: ' + error.message);
    }
}

// Delete category
async function deleteCategory(id, name) {
    const confirmed = await showConfirm(
        `Are you sure you want to delete "${name}"? You can undo this within 10 seconds.`,
        'Delete Category',
        true
    );
    if (!confirmed) return;

    try {
        // Get category data before deletion for undo
        const apiPath = `/api/index.php?request=categories/${id}`;
        const categoryData = await apiFetch(getApiUrl(apiPath));
        const originalCategory = categoryData.data || categoryData;

        const response = await apiFetch(getApiUrl(apiPath), {
            method: 'DELETE'
        });

        if (response.ok) {
            // Store action for undo
            lastBulkAction = {
                action: 'delete',
                categories: [{
                    id: parseInt(id),
                    name: originalCategory.name,
                    slug: originalCategory.slug,
                    description: originalCategory.description,
                    display_order: originalCategory.display_order || 0,
                    is_active: originalCategory.is_active
                }]
            };
            
            showUndoBar('delete', 1);
            loadCategories();
        } else {
            alert('Error deleting category');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Bulk Actions
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.category-checkbox:checked');
    const count = checkboxes.length;
    const bulkBar = document.getElementById('bulk-actions-bar');
    const selectedCountSpan = document.getElementById('selectedCount');
    const selectAll = document.getElementById('select-all');
    
    selectedCountSpan.textContent = count;
    bulkBar.style.display = count > 0 ? 'block' : 'none';
    
    const allCheckboxes = document.querySelectorAll('.category-checkbox');
    selectAll.checked = count > 0 && count === allCheckboxes.length;
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.category-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkActions();
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.category-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('select-all').checked = false;
    updateBulkActions();
}

function applyBulkAction() {
    const action = document.getElementById('bulk-action').value;
    if (!action) {
        alert('Please select an action');
        return;
    }
    
    if (action === 'delete') {
        bulkDeleteCategories();
    }
}

// Undo functionality
let lastBulkAction = null;
let undoTimeout = null;

function showUndoBar(action, count) {
    const undoBar = document.getElementById('undo-bar');
    const undoMessage = document.getElementById('undo-message');
    
    undoMessage.innerHTML = `<i class="fas fa-info-circle"></i> ${count} categor${count !== 1 ? 'ies' : 'y'} ${action}d`;
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
    
    const { action, categories } = lastBulkAction;
    dismissUndo();
    
    if (action === 'delete') {
        let successCount = 0;
        for (const catData of categories) {
            try {
                const res = await apiFetch(getApiUrl('/api/index.php?request=categories'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(catData)
                });
                if (res && res.success) successCount++;
            } catch (err) {
                console.error('Undo failed for category:', catData.name, err);
            }
        }
        alert(`Restored ${successCount} categor${successCount !== 1 ? 'ies' : 'y'}`);
        loadCategories();
    }
}

async function bulkDeleteCategories() {
    const checkboxes = document.querySelectorAll('.category-checkbox:checked');
    const categoryIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (categoryIds.length === 0) {
        alert('Please select categories to delete');
        return;
    }
    
    const confirmed = await showConfirm(
        `Are you sure you want to delete ${categoryIds.length} categor${categoryIds.length > 1 ? 'ies' : 'y'}? This action cannot be undone.`,
        'Delete Categories',
        true
    );
    if (!confirmed) return;
    
    try {
        // Save category data for undo
        const deletedCategories = [];
        for (const id of categoryIds) {
            const category = categoriesData.find(c => c.id == id);
            if (category) {
                deletedCategories.push({
                    id: parseInt(category.id),
                    name: category.name,
                    slug: category.slug,
                    description: category.description || '',
                    display_order: category.display_order || 0,
                    is_active: category.is_active
                });
            }
        }
        
        let successCount = 0;
        let failCount = 0;
        
        for (const categoryId of categoryIds) {
            try {
                const apiPath = `/api/index.php?request=categories/${categoryId}`;
                const res = await apiFetch(getApiUrl(apiPath), {
                    method: 'DELETE'
                });
                
                if (res && res.success) {
                    successCount++;
                } else {
                    failCount++;
                    console.error('Failed to delete category', categoryId);
                }
            } catch (err) {
                failCount++;
                console.error('Error deleting category', categoryId, err);
            }
        }
        
        if (successCount > 0) {
            lastBulkAction = { action: 'delete', categories: deletedCategories };
            showUndoBar('delete', successCount);
        }
        
        if (failCount > 0) {
            alert(`${successCount} deleted, ${failCount} failed`);
        }
        loadCategories();
        clearSelection();
        
    } catch (err) {
        alert('Server error. Please try again.');
        console.error(err);
    }
}

// Initialize
loadCategories();

// ===== ORGANIZE TAB FUNCTIONS =====

function switchTab(tab) {
    // Update buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.style.borderBottomColor = 'transparent';
        btn.style.color = '#666';
    });
    event.target.closest('.tab-btn').style.borderBottomColor = '#007bff';
    event.target.closest('.tab-btn').style.color = '#333';
    
    // Update tabs
    document.getElementById('manage-tab').style.display = tab === 'manage' ? 'block' : 'none';
    document.getElementById('organize-tab').style.display = tab === 'organize' ? 'block' : 'none';
    
    if (tab === 'organize') {
        loadOrganizeView();
    }
}

function loadOrganizeView() {
    currentOrder = JSON.parse(JSON.stringify(categoriesData.sort((a, b) => (a.position || 0) - (b.position || 0))));
    originalOrder = JSON.parse(JSON.stringify(currentOrder));
    
    // Build parent map FIRST - all categories including those without parent
    parentMap = {};
    currentOrder.forEach(cat => {
        if (cat.parent_id && cat.parent_id !== null && cat.parent_id !== undefined && cat.parent_id !== '') {
            // Use string key to handle both string and number parent_ids
            const parentKey = String(cat.parent_id);
            if (!parentMap[parentKey]) {
                parentMap[parentKey] = [];
            }
            parentMap[parentKey].push(cat);
        }
    });
    
    // Initialize visibility - show if position is set, hide if null
    visibilityMap = {};
    currentOrder.forEach(cat => {
        // Show main categories that have position set, hide those without
        visibilityMap[cat.id] = (cat.position !== null && cat.position !== undefined && cat.position !== '');
    });
    
    // Initialize expanded state - all expanded by default
    expandedCategories = {};
    const mainCategories = currentOrder.filter(cat => !cat.parent_id);
    mainCategories.forEach(cat => {
        expandedCategories[cat.id] = true;
    });
    
    renderOrganizeView();
}

function renderOrganizeView() {
    const mainContainer = document.getElementById('header-preview-container');
    const listContainer = document.getElementById('organize-list-container');
    
    const mainCategories = currentOrder
        .filter(cat => !cat.parent_id)
        .sort((a, b) => (a.position || 0) - (b.position || 0));
    
    const visibleMains = mainCategories.filter(cat => visibilityMap[cat.id]);
    const hiddenMains = mainCategories.filter(cat => !visibilityMap[cat.id]);
    const hiddenSubs = currentOrder.filter(cat => cat.parent_id && !visibilityMap[cat.id]);
    
    // ===== HEADER PREVIEW (Horizontal Dropdown Style) =====
    let previewHtml = '';
    visibleMains.forEach((mainCat) => {
        // Get subcategories - parent_id might be string or number, so compare as strings
        const subCats = (parentMap[mainCat.id] || parentMap[String(mainCat.id)] || [])
            .filter(sub => visibilityMap[sub.id])
            .sort((a, b) => (a.position || 0) - (b.position || 0));
        
        previewHtml += `
            <div style="position: relative; flex-shrink: 0;" class="preview-cat-item"
            onmouseenter="(() => { clearTimeout(this.hideTimer); const dd = this.querySelector('.preview-dropdown'); if(dd) dd.style.display='block'; }).call(this);"
            onmouseleave="this.hideTimer = setTimeout(() => { this.querySelector('.preview-dropdown') && (this.querySelector('.preview-dropdown').style.display='none'); }, 100);">
                <div style="
                    color: white;
                    padding: 5px 9px;
                    cursor: pointer;
                    border-radius: 3px;
                    background: rgba(255,255,255,0.1);
                    transition: all 0.2s;
                    white-space: nowrap;
                    font-weight: 500;
                    font-size: 11px;
                    display: inline-block;
                "
                onmouseenter="this.style.background='rgba(255,255,255,0.2)';"
                onmouseleave="this.style.background='rgba(255,255,255,0.1)';">
                    ${mainCat.name}
                    ${subCats.length > 0 ? '<i class="fas fa-chevron-down" style="margin-left: 4px; font-size: 9px;"></i>' : ''}
                </div>
                ${subCats.length > 0 ? `
                    <div class="preview-dropdown" style="
                        display: none;
                        position: absolute;
                        top: 100%;
                        left: 0;
                        background: white;
                        border: 1px solid #ddd;
                        border-radius: 3px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                        min-width: 160px;
                        z-index: 999999;
                        margin-top: 4px;
                        pointer-events: auto;
                    "
                    onmouseenter="clearTimeout(this.parentElement.hideTimer);"
                    onmouseleave="this.parentElement.hideTimer = setTimeout(() => { this.style.display='none'; }, 100);">
                        ${subCats.map(sub => `
                            <div style="
                                padding: 6px 10px;
                                border-bottom: 1px solid #f0f0f0;
                                color: #333;
                                cursor: pointer;
                                transition: all 0.2s;
                                font-size: 11px;
                                white-space: nowrap;
                            "
                            onmouseenter="this.style.backgroundColor='#f9f9f9';"
                            onmouseleave="this.style.backgroundColor='white';">
                                ${sub.name}
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        `;
    });
    
    if (visibleMains.length === 0) {
        previewHtml = '<p style="color: #999; margin: 0;">No visible categories. Use the reorder list below to show categories.</p>';
    }
    
    mainContainer.innerHTML = previewHtml;
    
    // ===== REORDER LIST (Draggable Items) =====
    let listHtml = '';
    mainCategories.forEach((mainCat, mainIndex) => {
        // Get subcategories - parent_id might be string or number
        const subCats = (parentMap[mainCat.id] || parentMap[String(mainCat.id)] || [])
            .sort((a, b) => (a.position || 0) - (b.position || 0));
        const isVisible = visibilityMap[mainCat.id];
        const hasSubs = subCats.length > 0;
        
        listHtml += `
            <div class="organize-item" style="
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 15px 20px;
                background: ${isVisible ? 'white' : '#f5f5f5'};
                border: 2px solid ${isVisible ? '#ddd' : '#ccc'};
                border-radius: 8px;
                margin-bottom: 12px;
                cursor: ${hasSubs ? 'pointer' : 'grab'};
                transition: all 0.2s;
                opacity: ${isVisible ? '1' : '0.7'};
            "
            draggable="true"
            data-type="main"
            data-id="${mainCat.id}"
            ondragstart="handleDragStart(event)"
            ondragend="handleDragEnd(event)"
            ondragover="handleDragOver(event)"
            ondrop="handleDrop(event)"
            onclick="${hasSubs ? `toggleExpand(${mainCat.id}, event)` : ''}"
            onmouseenter="this.style.borderColor='#007bff'; this.style.boxShadow='0 2px 8px rgba(0,123,255,0.2)'; this.style.backgroundColor='${isVisible ? '#f9fbff' : '#f5f5f5'}';"
            onmouseleave="this.style.borderColor='${isVisible ? '#ddd' : '#ccc'}'; this.style.boxShadow='none'; this.style.backgroundColor='${isVisible ? 'white' : '#f5f5f5'}';">
                
                <i class="fas fa-grip-vertical" style="cursor: grab; color: #999; font-size: 18px; min-width: 20px;"></i>
                
                ${hasSubs ? `
                    <div style="
                        cursor: pointer;
                        font-size: 14px;
                        color: ${isVisible ? '#007bff' : '#ccc'};
                        padding: 0;
                        transition: all 0.2s;
                        min-width: 20px;
                        text-align: center;
                    "
                    onmouseenter="this.style.color='${isVisible ? '#0056b3' : '#999'}'; this.style.transform='scale(1.2)';"
                    onmouseleave="this.style.color='${isVisible ? '#007bff' : '#ccc'}'; this.style.transform='scale(1)';">
                        <i class="fas fa-${expandedCategories[mainCat.id] ? 'chevron-down' : 'chevron-right'}"></i>
                    </div>
                ` : '<span style="min-width: 20px;"></span>'}
                
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; color: ${isVisible ? '#333' : '#999'}; font-size: 16px;">${mainCat.name}</div>
                    ${hasSubs ? `
                        <div style="font-size: 12px; color: ${isVisible ? '#999' : '#ccc'}; margin-top: 4px;">
                            ${subCats.length} subcategories
                        </div>
                    ` : `<div style="font-size: 12px; color: ${isVisible ? '#ccc' : '#ddd'}; margin-top: 4px;">Main category</div>`}
                </div>
                
                <span style="
                    background: #007bff;
                    color: white;
                    padding: 6px 12px;
                    border-radius: 12px;
                    font-size: 12px;
                    font-weight: 600;
                    white-space: nowrap;
                    min-width: 30px;
                    text-align: center;
                ">${mainIndex + 1}</span>
                
                <button type="button" style="
                    background: none;
                    border: none;
                    cursor: pointer;
                    font-size: 18px;
                    color: ${isVisible ? '#007bff' : '#ccc'};
                    padding: 0 10px;
                    transition: all 0.2s;
                    min-width: 30px;
                    text-align: center;
                "
                onclick="toggleVisibility(${mainCat.id}, event)"
                title="${isVisible ? 'Click to hide' : 'Click to show'}"
                onmouseenter="this.style.color='${isVisible ? '#0056b3' : '#999'}'; this.style.transform='scale(1.2)';"
                onmouseleave="this.style.color='${isVisible ? '#007bff' : '#ccc'}'; this.style.transform='scale(1)';">
                    <i class="fas fa-${isVisible ? 'eye' : 'eye-slash'}"></i>
                </button>
            </div>
            ${hasSubs && expandedCategories[mainCat.id] ? `
                <div style="margin: 12px 0 12px 40px; padding: 12px; background: #f9fbff; border-left: 3px solid #007bff; border-radius: 4px;">
                    ${subCats.map((subCat, subIndex) => {
                        const subIsVisible = visibilityMap[subCat.id];
                        return `
                        <div class="organize-item-sub" style="
                            display: flex;
                            align-items: center;
                            gap: 10px;
                            padding: 10px 12px;
                            background: white;
                            border: 1px solid #e0e0e0;
                            border-radius: 6px;
                            margin-bottom: ${subIndex === subCats.length - 1 ? '0' : '8px'};
                            cursor: grab;
                            transition: all 0.2s;
                            font-size: 14px;
                            opacity: ${subIsVisible ? '1' : '0.6'};
                        "
                        draggable="true"
                        data-type="sub"
                        data-id="${subCat.id}"
                        data-parent="${mainCat.id}"
                        ondragstart="handleDragStart(event)"
                        ondragend="handleDragEnd(event)"
                        ondragover="handleDragOver(event)"
                        ondrop="handleDrop(event)"
                        onmouseenter="this.style.borderColor='#007bff'; this.style.boxShadow='0 1px 4px rgba(0,123,255,0.15)'; this.style.backgroundColor='#f0f8ff';"
                        onmouseleave="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'; this.style.backgroundColor='white';">
                            
                            <i class="fas fa-grip-vertical" style="cursor: grab; color: #ccc; font-size: 14px; min-width: 16px;"></i>
                            
                            <div style="flex: 1; color: ${subIsVisible ? '#555' : '#999'};">
                                ${subCat.name}
                            </div>
                            
                            <button type="button" style="
                                background: none;
                                border: none;
                                cursor: pointer;
                                font-size: 14px;
                                color: ${subIsVisible ? '#007bff' : '#ccc'};
                                padding: 0 8px;
                                transition: all 0.2s;
                                min-width: 24px;
                                text-align: center;
                            "
                            onclick="toggleVisibility(${subCat.id}, event)"
                            title="${subIsVisible ? 'Click to hide' : 'Click to show'}"
                            onmouseenter="this.style.color='${subIsVisible ? '#0056b3' : '#999'}'; this.style.transform='scale(1.2)';"
                            onmouseleave="this.style.color='${subIsVisible ? '#007bff' : '#ccc'}'; this.style.transform='scale(1)';">
                                <i class="fas fa-${subIsVisible ? 'eye' : 'eye-slash'}"></i>
                            </button>
                        </div>
                        `;
                    }).join('')}
                </div>
            ` : ''}
        `;
    });
    
    listContainer.innerHTML = listHtml || '<p style="color: #999; text-align: center; padding: 20px;">No categories yet. Add one to get started!</p>';
    
    // Show hidden section only if there are hidden items
    const allHidden = [...hiddenMains, ...hiddenSubs];
    if (allHidden.length > 0) {
        // Optional: Add a "Hidden categories" summary at the bottom if needed
        // For now, hidden items are shown with eye-slash icon in the reorder list
    }
}

function renderHeaderPreview() {
    // This function is now merged into renderOrganizeView()
    // Keep it for backwards compatibility
    renderOrganizeView();
}

function toggleVisibility(categoryId, event) {
    event.stopPropagation();
    visibilityMap[categoryId] = !visibilityMap[categoryId];
    renderOrganizeView();
    updateChangeIndicator();
}

function toggleExpand(categoryId, event) {
    event.stopPropagation();
    expandedCategories[categoryId] = !expandedCategories[categoryId];
    renderOrganizeView();
}

function showAddCategoryModal() {
    // Just call openCategoryModal - it handles everything
    openCategoryModal();
}

let draggedElement = null;
let renderTimeout = null;

function handleDragStart(e) {
    draggedElement = e.currentTarget;
    draggedElement.style.opacity = '0.6';
    draggedElement.style.backgroundColor = '#e3f2fd';
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setDragImage(draggedElement, 0, 0);
}

function handleDragEnd(e) {
    if (draggedElement) {
        draggedElement.style.opacity = '1';
        draggedElement.style.backgroundColor = '';
        draggedElement = null;
    }
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (draggedElement && draggedElement !== e.currentTarget) {
        const target = e.currentTarget;
        target.style.borderColor = '#007bff';
        target.style.borderWidth = '2px';
        target.style.backgroundColor = '#f0f8ff';
    }
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    
    if (draggedElement && draggedElement !== e.currentTarget) {
        const draggedId = parseInt(draggedElement.dataset.id);
        const targetId = parseInt(e.currentTarget.dataset.id);
        const draggedType = draggedElement.dataset.type;
        const targetType = e.currentTarget.dataset.type;
        
        // Can only swap same types (both main or both sub under same parent)
        if (draggedType === targetType) {
            if (draggedType === 'sub') {
                const draggedParent = draggedElement.dataset.parent;
                const targetParent = e.currentTarget.dataset.parent;
                if (draggedParent !== targetParent) {
                    return; // Can't move subs between different parents
                }
            }
            
            // Find and swap positions in array
            const draggedIdx = currentOrder.findIndex(c => c.id === draggedId);
            const targetIdx = currentOrder.findIndex(c => c.id === targetId);
            
            if (draggedIdx !== -1 && targetIdx !== -1) {
                // Swap the objects in the array
                [currentOrder[draggedIdx], currentOrder[targetIdx]] = [currentOrder[targetIdx], currentOrder[draggedIdx]];
                
                // Update position values to match new array order
                currentOrder.forEach((item, index) => {
                    item.position = index;
                });
                
                updateChangeIndicator();
                renderOrganizeView();
            }
        }
    }
    
    // Reset target style
    const target = e.currentTarget;
    target.style.borderColor = '';
    target.style.borderWidth = '';
    target.style.backgroundColor = '';
}

function updateChangeIndicator() {
    const changed = JSON.stringify(originalOrder) !== JSON.stringify(currentOrder);
    const indicator = document.getElementById('changes-indicator');
    const saveBtn = document.getElementById('save-btn');
    
    if (changed) {
        indicator.classList.remove('hidden');
        saveBtn.disabled = false;
    } else {
        indicator.classList.add('hidden');
        saveBtn.disabled = true;
    }
}

async function saveOrder() {
    const btn = document.getElementById('save-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    try {
        const updates = currentOrder.map((cat, index) => ({
            id: cat.id,
            position: index
        }));
        
        const result = await apiFetch(getApiUrl('/api/categories/reorder.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ orders: updates })
        });
        
        if (result && result.success) {
            originalOrder = JSON.parse(JSON.stringify(currentOrder));
            document.getElementById('changes-indicator').classList.add('hidden');
            alert('Order saved successfully!');
            loadCategories();
        } else {
            alert('Error: ' + (result?.message || 'Failed to save order'));
        }
    } catch (error) {
        console.error('Error saving order:', error);
        alert('Error: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Order';
    }
}

// Add style for hidden indicator
const style = document.createElement('style');
style.textContent = `.hidden { display: none !important; }`;
document.head.appendChild(style);
</script>
        </main>
    </div>
    <?php include '../components/toast-notification.php'; ?>
</body>
</html>
