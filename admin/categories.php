<?php
require_once '../config.php';
require_once 'auth-check.php';

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Old auth code removed by update script
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Demolition Traders</title>
    <base href="<?php echo rtrim(FRONTEND_URL, '/'); ?>/">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/admin-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="<?php echo FRONTEND_URL; ?>/assets/js/api-helper.js"></script>
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
                    <th>Parent</th>
                    <th onclick="sortTable('count')" style="cursor: pointer;" title="Click to sort">
                        Products Count <i class="fas fa-sort" style="opacity: 0.3;"></i>
                    </th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="categories-tbody">
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                        <div class="spinner"></div>
                        <p>Loading categories...</p>
                    </td>
                </tr>
            </tbody>
        </table>
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
                <label class="form-label">Parent Category</label>
                <select class="form-control" id="category-parent">
                    <option value="">None (Top Level)</option>
                </select>
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

// Load categories
async function loadCategories() {
    const tbody = document.getElementById('categories-tbody');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px;"><div class="spinner"></div><p>Loading categories...</p></td></tr>';

    try {
        const response = await fetch(getApiUrl('/api/index.php?request=categories'));
        const responseText = await response.text();
        const data = JSON.parse(responseText);
        categoriesData = data.data || data;

        if (!categoriesData || categoriesData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px;">No categories found</td></tr>';
            return;
        }

        // Get product counts for each category
        const countsResponse = await fetch(getApiUrl('/api/index.php?request=products&per_page=1000'));
        const productsData = await countsResponse.json();
        const products = productsData.data || [];
        
        productCounts = {};
        products.forEach(p => {
            productCounts[p.category_id] = (productCounts[p.category_id] || 0) + 1;
        });

        renderCategories();
        populateParentSelect();
    } catch (error) {
        console.error('Error loading categories:', error);
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: red;">Error loading categories</td></tr>';
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
            <td data-label="Parent">${(categoriesData.find(c => c.id == category.parent_id) || {}).name || ''}</td>
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
    // Populate parent select with categories
    populateParentSelect();
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
        const response = await fetch(getApiUrl(apiPath));
        const responseText = await response.text();
        const category = JSON.parse(responseText);

        document.getElementById('category-modal-title').textContent = 'Edit Category';
        document.getElementById('category-id').value = category.id;
        document.getElementById('category-custom-id').value = category.id;
        document.getElementById('custom-id-group').style.display = 'none'; // Hide ID field when editing
        document.getElementById('category-name').value = category.name;
        document.getElementById('category-slug').value = category.slug;
        document.getElementById('category-description').value = category.description || '';
        document.getElementById('category-status').value = category.is_active;
        // Set parent
        populateParentSelect();
        document.getElementById('category-parent').value = category.parent_id || '';

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
        parent_id: (document.getElementById('category-parent').value ? parseInt(document.getElementById('category-parent').value) : null),
        is_active: document.getElementById('category-status').value
    };

    // Include custom ID only for new categories
    if (!id && customId) {
        data.id = parseInt(customId);
    }

    // Validate parent not same as self
    if (data.parent_id && id && String(data.parent_id) === String(id)) {
        alert('Invalid parent: a category cannot be its own parent');
        return;
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
            const responseText = await response.text();
            const error = JSON.parse(responseText);
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
        const categoryResponse = await fetch(getApiUrl(apiPath));
        const categoryData = await categoryResponse.json();
        const originalCategory = categoryData.data || categoryData;

        const response = await fetch(getApiUrl(apiPath), {
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

// Populate parent select options
function populateParentSelect() {
    const select = document.getElementById('category-parent');
    if (!select) return;
    const currentId = document.getElementById('category-id').value;
    let html = '<option value="">None (Top Level)</option>';
    const sorted = [...categoriesData].sort((a,b) => a.name.localeCompare(b.name));
    sorted.forEach(c => {
        // Prevent parent option being itself when editing
        if (currentId && String(c.id) === String(currentId)) return;
        html += `<option value="${c.id}">${c.name}</option>`;
    });
    select.innerHTML = html;
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
                const res = await fetch(getApiUrl('/api/index.php?request=categories'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(catData)
                });
                if (res.ok) successCount++;
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
                const res = await fetch(getApiUrl(apiPath), {
                    method: 'DELETE'
                });
                
                if (res.ok) {
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
</script>
        </main>
    </div>
    <?php include __DIR__ . '/../frontend/components/toast-notification.php'; ?>
</body>
</html>
