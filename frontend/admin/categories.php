<?php
require_once '../config.php';

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

    <!-- Header/Shop Preview - Drag to Reorder -->
    <div id="category-preview-section" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 12px; padding: 15px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div>
                <h3 style="color: #fff; margin: 0; font-size: 16px;">
                    <i class="fas fa-eye"></i> Header Menu Preview
                </h3>
                <p style="color: #a0a0a0; margin: 3px 0 0 0; font-size: 13px;">
                    Drag to reorder • Only showing main categories displayed in header/shop filter
                </p>
            </div>
            <div style="display: flex; gap: 8px;">
                <button class="btn btn-sm" onclick="resetPreviewOrder()" style="background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 5px; font-size: 12px;">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <button class="btn btn-sm" onclick="savePreviewOrder()" style="background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 5px; font-size: 12px;">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </div>
        
        <!-- Preview Navigation Bar -->
        <div style="background: #2d3436; border-radius: 6px; padding: 8px 12px; overflow-x: auto;">
            <div id="preview-nav-list" style="display: flex; gap: 4px; min-height: 32px; align-items: center;">
                <span style="color: #636e72; font-size: 13px;">Loading...</span>
            </div>
        </div>
        
        <!-- Order Status -->
        <div id="preview-status" style="margin-top: 10px; padding: 8px 12px; border-radius: 5px; display: none; font-size: 12px;">
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
                <option value="activate">Show (Activate)</option>
                <option value="deactivate">Hide (Deactivate)</option>
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
                    <th>Parent</th>
                    <th onclick="sortTable('order')" style="cursor: pointer;" title="Click to sort">
                        Order <i class="fas fa-sort" style="opacity: 0.3;"></i>
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
                    <option value="">None (Main Category)</option>
                    <!-- Options populated via JS -->
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Display Order</label>
                <input type="number" class="form-control" id="category-order" value="0">
                <small style="color: #6c757d;">Lower numbers appear first</small>
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
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;"><div class="spinner"></div><p>Loading categories...</p></td></tr>';

    try {
        const response = await fetch(getApiUrl('/api/index.php?request=categories'));
        const data = await response.json();
        categoriesData = data.data || data;

        if (!categoriesData || categoriesData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;">No categories found</td></tr>';
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
        loadPreview(); // Load preview section
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
                    <button class="btn btn-${category.is_active == 1 ? 'secondary' : 'success'} btn-sm" onclick="toggleCategoryStatus(${category.id}, ${category.is_active == 1 ? 0 : 1})" title="${category.is_active == 1 ? 'Hide (set Inactive)' : 'Show (set Active)'}">
                        <i class="fas fa-${category.is_active == 1 ? 'eye-slash' : 'eye'}"></i>
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

// Render categories to table
function renderCategories() {
    const tbody = document.getElementById('categories-tbody');
    
    // Organize into tree structure if not sorting by other columns
    let displayData = [];
    
    if (!currentSort.column || currentSort.column === 'order') {
        // Group by parent
        const mainCats = categoriesData.filter(c => !c.parent_id || c.parent_id == 0);
        const subCats = categoriesData.filter(c => c.parent_id && c.parent_id != 0);
        
        // Sort main categories by display_order
        mainCats.sort((a, b) => (a.display_order || 0) - (b.display_order || 0));
        
        mainCats.forEach(main => {
            displayData.push({ ...main, level: 0 });
            
            // Find children
            const children = subCats.filter(c => c.parent_id == main.id);
            children.sort((a, b) => (a.display_order || 0) - (b.display_order || 0));
            
            children.forEach(child => {
                displayData.push({ ...child, level: 1, parent_name: main.name });
            });
        });
        
        // Add orphans (sub-cats with missing parent)
        const orphans = subCats.filter(sub => !mainCats.find(main => main.id == sub.parent_id));
        orphans.forEach(orphan => {
            displayData.push({ ...orphan, level: 0, name: orphan.name + ' (Orphan)' });
        });
    } else {
        // Flat list for other sorts
        displayData = categoriesData.map(c => {
            const parent = categoriesData.find(p => p.id == c.parent_id);
            return { ...c, level: 0, parent_name: parent ? parent.name : '-' };
        });
    }

    tbody.innerHTML = displayData.map(category => {
        const indent = category.level * 30;
        const nameStyle = category.level > 0 ? `padding-left: ${indent}px; color: #666;` : 'font-weight: 600; color: #2f3192;';
        const icon = category.level > 0 ? '<i class="fas fa-level-up-alt fa-rotate-90" style="margin-right:8px; opacity:0.5;"></i>' : '';
        
        return `
        <tr data-id="${category.id}" data-name="${category.name.toLowerCase()}" data-count="${productCounts[category.id] || 0}">
            <td>
                <input type="checkbox" class="category-checkbox" value="${category.id}" onchange="updateBulkActions()">
            </td>
            <td data-label="ID"><strong>${category.id}</strong></td>
            <td data-label="Name" style="${nameStyle}">
                ${icon}${category.name}
            </td>
            <td data-label="Parent">${category.parent_name || '-'}</td>
            <td data-label="Order">${category.display_order || 0}</td>
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
    `}).join('');
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
        } else if (column === 'order') {
            aVal = parseInt(a.display_order || 0);
            bVal = parseInt(b.display_order || 0);
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

    // If sorting by order, we might want to reset to tree view logic if direction is asc
    // But for now, let's just sort flat list
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
        } else if (column === 'order') {
            aVal = parseInt(a.display_order || 0);
            bVal = parseInt(b.display_order || 0);
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
    const dropdown = document.getElementById('sort-by');
    if (dropdown.querySelector(`option[value="${sortValue}"]`)) {
        dropdown.value = sortValue;
    }

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

// Populate Parent Dropdown
function populateParentDropdown(selectedParentId = null, currentId = null) {
    const select = document.getElementById('category-parent');
    select.innerHTML = '<option value="">None (Main Category)</option>';
    
    // Only show main categories as parents
    const mainCats = categoriesData.filter(c => (!c.parent_id || c.parent_id == 0) && c.id != currentId);
    
    // Sort alphabetically
    mainCats.sort((a, b) => a.name.localeCompare(b.name));
    
    mainCats.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name;
        if (selectedParentId && cat.id == selectedParentId) {
            option.selected = true;
        }
        select.appendChild(option);
    });
}

// Open modal
function openCategoryModal() {
    document.getElementById('category-modal-title').textContent = 'Add New Category';
    document.getElementById('category-form').reset();
    document.getElementById('category-id').value = '';
    document.getElementById('category-custom-id').value = '';
    document.getElementById('custom-id-group').style.display = 'block'; // Show ID field for new category
    document.getElementById('category-order').value = '0';
    
    populateParentDropdown();
    
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
        const category = await response.json();

        document.getElementById('category-modal-title').textContent = 'Edit Category';
        document.getElementById('category-id').value = category.id;
        document.getElementById('category-custom-id').value = category.id;
        document.getElementById('custom-id-group').style.display = 'none'; // Hide ID field when editing
        document.getElementById('category-name').value = category.name;
        document.getElementById('category-slug').value = category.slug;
        document.getElementById('category-description').value = category.description || '';
        document.getElementById('category-status').value = category.is_active;
        document.getElementById('category-order').value = category.display_order || 0;
        
        populateParentDropdown(category.parent_id, category.id);

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
        is_active: document.getElementById('category-status').value,
        parent_id: document.getElementById('category-parent').value || null,
        display_order: document.getElementById('category-order').value || 0
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
    } else if (action === 'activate') {
        bulkUpdateStatus(1);
    } else if (action === 'deactivate') {
        bulkUpdateStatus(0);
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

async function bulkUpdateStatus(status) {
    const checkboxes = document.querySelectorAll('.category-checkbox:checked');
    const categoryIds = Array.from(checkboxes).map(cb => cb.value);
    if (categoryIds.length === 0) {
        alert('Please select categories to update');
        return;
    }

    const verb = status == 1 ? 'activate' : 'deactivate';
    const confirmed = await showConfirm(
        `Are you sure you want to ${verb} ${categoryIds.length} categor${categoryIds.length > 1 ? 'ies' : 'y'}?`,
        status == 1 ? 'Show Categories' : 'Hide Categories',
        false
    );
    if (!confirmed) return;

    let successCount = 0;
    for (const id of categoryIds) {
        try {
            const res = await fetch(getApiUrl(`/api/index.php?request=categories/${id}`), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ is_active: status })
            });
            if (res.ok) successCount++;
        } catch (err) {
            console.error('Status update failed for category', id, err);
        }
    }

    alert(`Updated ${successCount} categor${successCount !== 1 ? 'ies' : 'y'}`);
    loadCategories();
    clearSelection();
}

async function toggleCategoryStatus(id, status) {
    try {
        const res = await fetch(getApiUrl(`/api/index.php?request=categories/${id}`), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ is_active: status })
        });
        if (res.ok) {
            loadCategories();
        } else {
            const err = await res.json();
            alert('Error updating status: ' + (err.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error updating status: ' + error.message);
    }
}

// =====================================================
// PREVIEW SECTION - Drag & Drop Reorder
// =====================================================

// Categories shown in header/shop filter
const headerCategories = [
    "Plywood", "Doors", "Windows", "Sliding Doors", "Timber", 
    "Cladding", "Landscaping", "Roofing", "Kitchens", 
    "Bathroom & Laundry", "General"
];

let previewCategories = [];
let originalOrder = [];
let draggedItem = null;

// Load and render preview
function loadPreview() {
    if (!categoriesData || categoriesData.length === 0) return;
    
    // Filter main categories that are in headerCategories list
    previewCategories = categoriesData.filter(c => {
        const isRoot = !c.parent_id || c.parent_id == 0;
        const isInList = headerCategories.some(name => 
            name.toLowerCase() === (c.name || '').toLowerCase()
        );
        return isRoot && isInList;
    });
    
    // Sort by display_order
    previewCategories.sort((a, b) => {
        const orderA = Number(a.display_order) || 999;
        const orderB = Number(b.display_order) || 999;
        return orderA - orderB;
    });
    
    // Save original order for reset
    originalOrder = previewCategories.map(c => ({ id: c.id, order: c.display_order }));
    
    renderPreview();
}

function renderPreview() {
    const container = document.getElementById('preview-nav-list');
    if (!container) return;
    
    container.innerHTML = previewCategories.map((cat, index) => `
        <div class="preview-nav-item" 
             draggable="true" 
             data-id="${cat.id}" 
             data-index="${index}"
             style="
                background: ${cat.is_active == 1 ? '#0984e3' : '#636e72'};
                color: white;
                padding: 7px 14px;
                border-radius: 4px;
                cursor: grab;
                font-size: 13px;
                font-weight: 500;
                white-space: nowrap;
                user-select: none;
                display: flex;
                align-items: center;
                gap: 7px;
                transition: all 0.2s ease;
                opacity: ${cat.is_active == 1 ? '1' : '0.6'};
             "
             onmouseenter="this.style.transform='scale(1.03)'"
             onmouseleave="this.style.transform='scale(1)'"
        >
            <i class="fas fa-grip-vertical" style="opacity: 0.5; font-size: 11px;"></i>
            <span>${escapeHtml(cat.name)}</span>
            <span style="background: rgba(255,255,255,0.2); padding: 2px 7px; border-radius: 3px; font-size: 11px;">${index + 1}</span>
        </div>
    `).join('');
    
    // Add drag event listeners
    const items = container.querySelectorAll('.preview-nav-item');
    items.forEach(item => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('dragenter', handleDragEnter);
        item.addEventListener('dragleave', handleDragLeave);
    });
    
    updatePreviewStatus();
}

function handleDragStart(e) {
    draggedItem = this;
    this.style.opacity = '0.4';
    this.style.cursor = 'grabbing';
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

function handleDragEnd(e) {
    this.style.opacity = this.dataset.active === '0' ? '0.6' : '1';
    this.style.cursor = 'grab';
    document.querySelectorAll('.preview-nav-item').forEach(item => {
        item.style.borderLeft = 'none';
        item.style.borderRight = 'none';
    });
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e) {
    e.preventDefault();
    if (this !== draggedItem) {
        this.style.borderLeft = '3px solid #00b894';
    }
}

function handleDragLeave(e) {
    this.style.borderLeft = 'none';
}

function handleDrop(e) {
    e.stopPropagation();
    
    if (draggedItem !== this) {
        const container = document.getElementById('preview-nav-list');
        const items = Array.from(container.children);
        const draggedIndex = items.indexOf(draggedItem);
        const targetIndex = items.indexOf(this);
        
        // Reorder in array
        const [removed] = previewCategories.splice(draggedIndex, 1);
        previewCategories.splice(targetIndex, 0, removed);
        
        // Re-render
        renderPreview();
    }
    
    return false;
}

function updatePreviewStatus() {
    const statusEl = document.getElementById('preview-status');
    if (!statusEl) return;
    
    // Check if order changed
    let hasChanges = false;
    previewCategories.forEach((cat, index) => {
        const newOrder = index + 1;
        if (Number(cat.display_order) !== newOrder) {
            hasChanges = true;
        }
    });
    
    if (hasChanges) {
        statusEl.style.display = 'block';
        statusEl.style.background = 'rgba(241, 196, 15, 0.2)';
        statusEl.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle" style="color: #f1c40f;"></i>
                <span style="color: #f1c40f; font-weight: 500;">Unsaved changes! Click "Save Order" to apply.</span>
            </div>
        `;
    } else {
        statusEl.style.display = 'block';
        statusEl.style.background = 'rgba(39, 174, 96, 0.2)';
        statusEl.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                <span style="color: #27ae60;">Order is saved and synced with header/shop.</span>
            </div>
        `;
    }
}

async function savePreviewOrder() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    try {
        let successCount = 0;
        
        for (let i = 0; i < previewCategories.length; i++) {
            const cat = previewCategories[i];
            const newOrder = i + 1;
            
            const res = await fetch(getApiUrl(`/api/index.php?request=categories/${cat.id}`), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ display_order: newOrder })
            });
            
            if (res.ok) {
                successCount++;
                cat.display_order = newOrder;
            }
        }
        
        // Update originalOrder
        originalOrder = previewCategories.map(c => ({ id: c.id, order: c.display_order }));
        
        // Show success
        showToast(`Order saved! ${successCount} categories updated.`, 'success');
        updatePreviewStatus();
        
        // Reload main table to reflect changes
        loadCategories();
        
    } catch (error) {
        console.error('Error saving order:', error);
        showToast('Error saving order. Please try again.', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

function resetPreviewOrder() {
    // Restore original order
    previewCategories.forEach(cat => {
        const original = originalOrder.find(o => o.id === cat.id);
        if (original) {
            cat.display_order = original.order;
        }
    });
    
    // Re-sort by original display_order
    previewCategories.sort((a, b) => {
        const orderA = Number(a.display_order) || 999;
        const orderB = Number(b.display_order) || 999;
        return orderA - orderB;
    });
    
    renderPreview();
    showToast('Order reset to last saved state.', 'info');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    // Check if toast system exists
    if (typeof window.showToastNotification === 'function') {
        window.showToastNotification(message, type);
        return;
    }
    
    // Fallback: create simple toast
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i> ${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .preview-nav-item:active {
        cursor: grabbing !important;
    }
`;
document.head.appendChild(style);

// =====================================================
// END PREVIEW SECTION
// =====================================================

// Initialize
loadCategories();
</script>
        </main>
    </div>
    <?php include '../components/toast-notification.php'; ?>
</body>
</html>
