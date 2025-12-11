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
    <title>Products Management - Demolition Traders</title>
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

<!-- Products Management Content -->
<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">All Products</h2>
        <button class="btn btn-primary" onclick="openProductModal()">
            <i class="fas fa-plus"></i> Add New Product
        </button>
    </div>
    <!-- Info Notice -->
    <div style="background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px 20px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-info-circle" style="color: #0c5460; font-size: 20px;"></i>
        <div style="flex: 1;">
            <strong style="color: #0c5460; font-size: 15px;">ℹ️ Undo Available:</strong>
            <p style="margin: 5px 0 0 0; color: #0c5460; font-size: 14px;">Deleted products can be <strong>restored within 10 seconds</strong> using the Undo button.</p>
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="search-products" placeholder="Search products by name, SKU..." onkeyup="searchProducts()">
        <select id="filter-category" class="form-control" style="max-width: 200px;" onchange="loadProducts()">
            <option value="">All Categories</option>
        </select>
        <select id="filter-condition" class="form-control" style="max-width: 150px;" onchange="loadProducts()">
            <option value="">All Conditions</option>
            <option value="new">New</option>
            <option value="recycled">Recycled</option>
        </select>
        <select id="filter-status" class="form-control" style="max-width: 150px;" onchange="loadProducts()">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <!-- Bulk Actions Bar -->
    <div id="bulk-actions-bar" style="display: none;">
        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <span id="selected-count">
                <i class="fas fa-check-circle"></i> 0 items selected
            </span>
            <select id="bulk-action">
                <option value="">Select Action</option>
                <option value="activate">Set Active</option>
                <option value="deactivate">Set Inactive</option>
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

    <!-- Custom Confirm Modal -->
    <div id="custom-confirm-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h3 style="margin: 0 0 15px 0; color: #2f3192; font-size: 20px;">
                <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i> Confirm Action
            </h3>
            <p id="confirm-message" style="margin: 0 0 25px 0; font-size: 15px; color: #333; line-height: 1.5;"></p>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="resolveConfirm(false)" style="padding: 10px 20px;">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-primary" onclick="resolveConfirm(true)" style="padding: 10px 20px;">
                    <i class="fas fa-check"></i> Confirm
                </button>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table id="products-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
                    </th>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Condition</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="products-tbody">
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px;">
                        <div class="spinner"></div>
                        <p>Loading products...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="pagination" id="products-pagination"></div>
</div>

<!-- Product Modal -->
<div class="modal" id="product-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title">Add New Product</h3>
            <button class="close-modal" onclick="closeProductModal()">&times;</button>
        </div>
        <form id="product-form" onsubmit="saveProduct(event)">
            <input type="hidden" id="product-id">
            
            <div class="form-group">
                <label class="form-label">Product Name *</label>
                <input type="text" class="form-control" id="product-name" required>
            </div>

            <div class="form-group">
                <label class="form-label">Product Images</label>
                <input type="file" class="form-control" id="product-images" name="product-images" accept="image/*" multiple onchange="previewProductImages()">
                <div id="product-images-preview" style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px;"></div>
                <small>Select multiple images (jpg, png, jpeg, webp, gif). The first image will be the main image. You can remove images before saving.</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">SKU *</label>
                    <input type="text" class="form-control" id="product-sku" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select class="form-control" id="product-category" required></select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-control" id="product-description"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Price *</label>
                    <input type="number" step="0.01" class="form-control" id="product-price" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Quantity *</label>
                    <input type="number" class="form-control" id="product-stock" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Condition *</label>
                    <select class="form-control" id="product-condition" required>
                        <option value="new">New</option>
                        <option value="recycled">Recycled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select class="form-control" id="product-status" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 10px;">
                <label class="form-label">
                    <input type="checkbox" id="product-collection-options">
                    Collection Options (Show collection/delivery info in product footer)
                </label>
                <div style="font-size: 13px; color: #555; margin-top: 5px; margin-left: 24px;">
                    <b>OPTION A: FREE PICKUP</b><br>
                    - Pick up FREE from our Hamilton store.<br>
                    (There is no minimum sheet quantity required for pickup orders.)<br><br>
                    <b>OPTION B: DELIVERY - QUOTE REQUIRED</b><br>
                    1. Minimum Order: Strict 10-sheet minimum for delivery.<br>
                    2. Freight Quote: Due to size/weight variability, freight is NOT included.<br><br>
                    <b>TO GET A QUOTE:</b> Click 'Enquire' and provide your Quantity (min. 10) and Delivery Suburb & Postcode. We will reply with the freight cost to add to your order.
                </div>
            </div>

            <div class="form-group" style="margin-top: 10px;">
                <label class="form-label">
                    <input type="checkbox" id="product-featured">
                    Featured (Show this product in homepage featured gallery)
                </label>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Product
                </button>
                <button type="button" class="btn btn-danger" onclick="closeProductModal()" style="flex: 1;">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Preview selected images
// Preview and remove selected images// Preview and remove selected images
let selectedProductImages = [];
let existingProductImages = [];
let removedProductImageIds = [];

function previewProductImages() {
    const input = document.getElementById('product-images');
    selectedProductImages = Array.from(input.files);
    renderProductImagesPreview();
}

function renderProductImagesPreview() {
    const preview = document.getElementById('product-images-preview');
    preview.innerHTML = '';
    
    // Show existing images (from DB)
    existingProductImages.forEach((imgObj) => {
        if (removedProductImageIds.includes(imgObj.id)) return;
        
        const wrapper = document.createElement('div');
        wrapper.style.cssText = 'position:relative;display:inline-block;margin-right:8px;margin-bottom:8px;';
        
        const img = document.createElement('img');
        img.src = imgObj.url;
        img.style.cssText = 'max-width:80px;max-height:80px;object-fit:cover;border:1px solid #ccc;border-radius:4px;display:block;';
        
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.innerHTML = '×';
        btn.title = 'Remove image';
        btn.style.cssText = 'position:absolute;top:2px;right:2px;background:rgba(0,0,0,0.7);color:#fff;border:none;border-radius:50%;width:22px;height:22px;cursor:pointer;font-size:16px;line-height:1;';
        btn.onclick = () => removeExistingProductImage(imgObj.id);
        
        wrapper.appendChild(img);
        wrapper.appendChild(btn);
        preview.appendChild(wrapper);
    });
    
    // Show new images (from input)
    selectedProductImages.forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const wrapper = document.createElement('div');
            wrapper.style.cssText = 'position:relative;display:inline-block;margin-right:8px;margin-bottom:8px;';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'max-width:80px;max-height:80px;object-fit:cover;border:1px solid #ccc;border-radius:4px;display:block;';
            
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = '×';
            btn.title = 'Remove image';
            btn.style.cssText = 'position:absolute;top:2px;right:2px;background:rgba(0,0,0,0.7);color:#fff;border:none;border-radius:50%;width:22px;height:22px;cursor:pointer;font-size:16px;line-height:1;';
            btn.onclick = () => removeProductImage(idx);
            
            wrapper.appendChild(img);
            wrapper.appendChild(btn);
            preview.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    });
}

function removeExistingProductImage(imgId) {
    if (!removedProductImageIds.includes(imgId)) {
        removedProductImageIds.push(imgId);
    }
    renderProductImagesPreview();
}

function removeProductImage(idx) {
    selectedProductImages.splice(idx, 1);
    const input = document.getElementById('product-images');
    const dt = new DataTransfer();
    selectedProductImages.forEach(f => dt.items.add(f));
    input.files = dt.files;
    renderProductImagesPreview();
}

let currentPage = 1;
let totalPages = 1;
let lastBulkAction = null; // Store last action for undo
let confirmResolve = null; // For custom confirm modal
let selectedProductIds = new Set(); // Store selected product IDs across filters
let selectedProductsStatus = {}; // Store status of selected products {id: isActive}

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

// Load products
async function loadProducts(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('products-tbody');
    tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px;"><div class="spinner"></div><p>Loading products...</p></td></tr>';

    try {
        const search = document.getElementById('search-products').value;
        const category = document.getElementById('filter-category').value;
        const condition = document.getElementById('filter-condition').value;
        const status = document.getElementById('filter-status').value;

        const apiPath = `/api/index.php?request=products&page=${page}&per_page=20`;
        let url = getApiUrl(apiPath);
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (category) url += `&category=${category}`;
        if (condition) url += `&condition=${condition}`;
        if (status) {
            // Convert status to is_active boolean
            const isActive = status === 'active' ? '1' : '0';
            url += `&is_active=${isActive}`;
        }

        const response = await fetch(url);
        const responseText = await response.text();
        const data = JSON.parse(responseText);

        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px;">No products found</td></tr>';
            return;
        }

        tbody.innerHTML = data.data.map(product => `
            <tr>
                <td data-label="Select">
                    <input type="checkbox" class="product-checkbox" value="${product.id}" 
                           ${selectedProductIds.has(parseInt(product.id)) ? 'checked' : ''} 
                           onchange="toggleProductSelection(${product.id})">
                </td>
                <td data-label="SKU"><strong>${product.sku}</strong></td>
                <td data-label="Product Name">${product.name.substring(0, 50)}${product.name.length > 50 ? '...' : ''}</td>
                <td data-label="Category">${product.category_name || 'N/A'}</td>
                <td data-label="Price"><strong>$${parseFloat(product.price).toFixed(2)}</strong></td>
                <td data-label="Stock">${product.stock_quantity}</td>
                <td data-label="Condition">
                    <span class="badge badge-${product.condition_type}">${product.condition_type}</span>
                </td>
                <td data-label="Status">
                    <span class="badge badge-${product.is_active == 1 ? 'active' : 'inactive'}">
                        ${product.is_active == 1 ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td data-label="Actions">
                    <div class="action-btns">
                        <button class="btn btn-warning btn-sm" onclick="editProduct(${product.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteProduct(${product.id}, '${product.name.replace(/'/g, "\\'")}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        if (data.pagination) {
            totalPages = data.pagination.total_pages;
            updatePagination(data.pagination);
        }
        
        // Update bulk actions bar after restoring selection
        updateBulkActionsBar();
    } catch (error) {
        console.error('Error loading products:', error);
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: red;">Error loading products</td></tr>';
    }
}

// Bulk Actions Functions
function toggleProductSelection(productId) {
    // Ensure productId is always a number
    const id = parseInt(productId);
    const checkbox = document.querySelector(`.product-checkbox[value="${id}"]`);
    if (checkbox && checkbox.checked) {
        selectedProductIds.add(id);
        // Store status
        const row = checkbox.closest('tr');
        const statusCell = row.cells[7];
        const isActive = statusCell && statusCell.textContent.trim() === 'Active';
        selectedProductsStatus[id] = isActive;
        console.log('Added ID:', id, 'isActive:', isActive, 'Set:', Array.from(selectedProductIds));
    } else {
        selectedProductIds.delete(id);
        delete selectedProductsStatus[id];
        console.log('Removed ID:', id, 'Set:', Array.from(selectedProductIds));
    }
    updateBulkActionsBar();
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
        const productId = parseInt(cb.value);
        if (checkbox.checked) {
            selectedProductIds.add(productId);
            // Store status
            const row = cb.closest('tr');
            const statusCell = row.cells[7];
            const isActive = statusCell && statusCell.textContent.trim() === 'Active';
            selectedProductsStatus[productId] = isActive;
        } else {
            selectedProductIds.delete(productId);
            delete selectedProductsStatus[productId];
        }
    });
    updateBulkActionsBar();
}

function updateBulkActionsBar() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    const count = checkboxes.length;
    const totalSelected = selectedProductIds.size; // Total items in Set
    const bulkBar = document.getElementById('bulk-actions-bar');
    const selectedCount = document.getElementById('selected-count');
    const selectAll = document.getElementById('select-all');
    const bulkActionSelect = document.getElementById('bulk-action');
    
    if (totalSelected > 0) {
        bulkBar.style.display = 'flex';
        
        // Show total from Set, and indicate visible count if different
        if (count === totalSelected) {
            selectedCount.innerHTML = `<i class="fas fa-check-circle"></i> ${totalSelected} item${totalSelected > 1 ? 's' : ''} selected`;
        } else {
            selectedCount.innerHTML = `<i class="fas fa-check-circle"></i> ${totalSelected} item${totalSelected > 1 ? 's' : ''} selected <span style="opacity: 0.7;">(${count} visible)</span>`;
        }
        
        // Check status of ALL selected products (from stored status object)
        let hasActive = false;
        let hasInactive = false;
        
        for (const id of selectedProductIds) {
            if (selectedProductsStatus[id] === true) {
                hasActive = true;
            } else if (selectedProductsStatus[id] === false) {
                hasInactive = true;
            }
            // Break early if we found both
            if (hasActive && hasInactive) break;
        }
        
        // Rebuild select options based on ALL selected items status
        let options = '<option value="">Select Action</option>';
        
        if (hasInactive && !hasActive) {
            // Only inactive items selected - show activate only
            options += '<option value="activate">Set Active</option>';
        } else if (hasActive && !hasInactive) {
            // Only active items selected - show deactivate only
            options += '<option value="deactivate">Set Inactive</option>';
        } else {
            // Mixed selection or unknown status - show both
            options += '<option value="activate">Set Active</option>';
            options += '<option value="deactivate">Set Inactive</option>';
        }
        
        options += '<option value="delete">Delete Selected</option>';
        bulkActionSelect.innerHTML = options;
        
    } else {
        bulkBar.style.display = 'none';
        selectAll.checked = false;
        // Reset to default options
        bulkActionSelect.innerHTML = `
            <option value="">Select Action</option>
            <option value="activate">Set Active</option>
            <option value="deactivate">Set Inactive</option>
            <option value="delete">Delete Selected</option>
        `;
    }
}

function clearSelection() {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('select-all').checked = false;
    document.getElementById('bulk-action').value = '';
    selectedProductIds.clear(); // Clear the Set
    selectedProductsStatus = {}; // Clear the status object
    updateBulkActionsBar();
}

async function applyBulkAction() {
    const action = document.getElementById('bulk-action').value;
    if (!action) {
        showNotification('Please select an action', 'warning');
        return;
    }
    
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    const productIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (productIds.length === 0) {
        showNotification('Please select at least one product', 'warning');
        return;
    }
    
    // Confirm before proceeding
    const actionText = action === 'delete' ? 'delete' : action === 'activate' ? 'activate' : 'deactivate';
    const confirmMessage = action === 'delete' 
        ? `Are you sure you want to delete ${productIds.length} product(s)? You can undo this within 10 seconds.`
        : `Are you sure you want to ${actionText} ${productIds.length} product(s)?`;
    
    console.log('Showing custom confirm dialog...');
    
    // Use custom confirm modal
    const userConfirmed = await customConfirm(confirmMessage);
    
    console.log('User confirmed:', userConfirmed);
    
    if (!userConfirmed) {
        console.log('User cancelled the action');
        return;
    }
    
    console.log('Proceeding with bulk action...');
    
    try {
        // Show loading state
        const bulkBar = document.getElementById('bulk-actions-bar');
        bulkBar.style.opacity = '0.7';
        bulkBar.style.pointerEvents = 'none';
        
        // Store original data for undo (including delete)
        const originalStates = [];
        for (const productId of productIds) {
            if (action === 'delete') {
                // Fetch full product data before deletion
                try {
                    const apiPath = `/api/index.php?request=products/${productId}`;
                    const response = await fetch(getApiUrl(apiPath));
                    const responseText = await response.text();
                    const productData = JSON.parse(responseText);
                    if (productData) {
                        originalStates.push({ 
                            id: productId, 
                            fullData: productData,
                            action: 'delete' 
                        });
                    }
                } catch (error) {
                    console.error(`Error fetching product ${productId}:`, error);
                }
            } else {
                const row = document.querySelector(`input.product-checkbox[value="${productId}"]`)?.closest('tr');
                if (row) {
                    const statusBadge = row.querySelector('.badge-active, .badge-inactive');
                    const isActive = statusBadge?.classList.contains('badge-active') ? 1 : 0;
                    originalStates.push({ id: productId, is_active: isActive });
                }
            }
        }
        
        let successCount = 0;
        let failCount = 0;
        
        for (const productId of productIds) {
            try {
                const apiPath = `/api/index.php?request=products/${productId}`;
                if (action === 'delete') {
                    const response = await fetch(getApiUrl(apiPath), {
                        method: 'DELETE'
                    });
                    if (response.ok) successCount++;
                    else failCount++;
                } else {
                    const isActive = action === 'activate' ? 1 : 0;
                    const response = await fetch(getApiUrl(apiPath), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ is_active: isActive })
                    });
                    if (response.ok) successCount++;
                    else failCount++;
                }
            } catch (error) {
                console.error(`Error processing product ${productId}:`, error);
                failCount++;
            }
        }
        
        // Restore bar state
        bulkBar.style.opacity = '1';
        bulkBar.style.pointerEvents = 'auto';
        
        // Show result with undo option
        if (failCount > 0) {
            showNotification(`Completed: ${successCount} successful, ${failCount} failed`, 'warning');
        } else {
            // Store action for undo (including delete now!)
            lastBulkAction = {
                action: action,
                products: originalStates,
                count: successCount
            };
            showUndoBar(action, successCount);
            
            const actionText = action === 'delete' ? 'Deleted' : action === 'activate' ? 'Activated' : 'Deactivated';
            showNotification(`${actionText} ${successCount} product(s) successfully!`, 'success');
        }
        
        // Clear selection after successful action
        clearSelection();
        loadProducts(currentPage);
        
    } catch (error) {
        console.error('Bulk action error:', error);
        showNotification('Failed to perform bulk action', 'error');
    }
}

// Undo functionality
function showUndoBar(action, count) {
    const undoBar = document.getElementById('undo-bar');
    const undoMessage = document.getElementById('undo-message');
    
    const actionText = action === 'delete' ? 'deleted' : action === 'activate' ? 'activated' : 'deactivated';
    undoMessage.innerHTML = `<i class="fas fa-info-circle"></i> ${count} product(s) ${actionText}`;
    
    undoBar.style.display = 'flex';
    
    // Auto-hide after 10 seconds
    if (window.undoTimeout) clearTimeout(window.undoTimeout);
    window.undoTimeout = setTimeout(() => {
        dismissUndo();
    }, 10000);
}

function dismissUndo() {
    const undoBar = document.getElementById('undo-bar');
    undoBar.style.display = 'none';
    lastBulkAction = null;
    if (window.undoTimeout) clearTimeout(window.undoTimeout);
}

async function undoLastAction() {
    if (!lastBulkAction) {
        showNotification('No action to undo', 'warning');
        return;
    }
    
    const undoBar = document.getElementById('undo-bar');
    undoBar.style.opacity = '0.7';
    undoBar.style.pointerEvents = 'none';
    
    try {
        let successCount = 0;
        let failCount = 0;
        
        if (lastBulkAction.action === 'delete') {
            // Restore deleted products by recreating them
            for (const product of lastBulkAction.products) {
                if (product.fullData) {
                    try {
                        // Create product with original data including SKU
                        const formData = new FormData();
                        const data = product.fullData;
                        
                        formData.append('name', data.name);
                        formData.append('sku', data.sku); // Preserve original SKU
                        formData.append('description', data.description || '');
                        formData.append('price', data.price);
                        formData.append('stock_quantity', data.stock_quantity);
                        formData.append('category_id', data.category_id);
                        formData.append('condition_type', data.condition_type);
                        formData.append('is_active', data.is_active);
                        if (data.is_featured) formData.append('is_featured', data.is_featured);
                        if (data.slug) formData.append('slug', data.slug); // Preserve original slug
                        
                        const response = await fetch(getApiUrl('/api/index.php?request=products'), {
                            method: 'POST',
                            body: formData
                        });
                        
                        if (response.ok) successCount++;
                        else failCount++;
                    } catch (error) {
                        console.error(`Error restoring product ${product.id}:`, error);
                        failCount++;
                    }
                }
            }
        } else {
            // Restore original active status
            for (const product of lastBulkAction.products) {
                try {
                    const apiPath = `/api/index.php?request=products/${product.id}`;
                    const response = await fetch(getApiUrl(apiPath), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ is_active: product.is_active })
                    });
                    if (response.ok) successCount++;
                    else failCount++;
                } catch (error) {
                    console.error(`Error restoring product ${product.id}:`, error);
                    failCount++;
                }
            }
        }
        
        if (failCount > 0) {
            showNotification(`Undo completed: ${successCount} successful, ${failCount} failed`, 'warning');
        } else {
            showNotification(`Successfully undone ${successCount} product(s)!`, 'success');
        }
        
        dismissUndo();
        loadProducts(currentPage);
        
    } catch (error) {
        console.error('Undo error:', error);
        showNotification('Failed to undo action', 'error');
        undoBar.style.opacity = '1';
        undoBar.style.pointerEvents = 'auto';
    }
}

// Enhanced notification function
function showNotification(message, type = 'info') {
    // Remove existing notification if any
    const existing = document.querySelector('.bulk-notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = 'bulk-notification';
    notification.textContent = message;
    
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        z-index: 10000;
        font-weight: 500;
        animation: slideInRight 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function updatePagination(pagination) {
    const container = document.getElementById('products-pagination');
    let html = '';

    if (pagination.current_page > 1) {
        html += `<button onclick="loadProducts(${pagination.current_page - 1})">Previous</button>`;
    }

    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || 
            (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            html += `<button class="${i === pagination.current_page ? 'active' : ''}" onclick="loadProducts(${i})">${i}</button>`;
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            html += '<span style="padding: 8px;">...</span>';
        }
    }

    if (pagination.current_page < pagination.total_pages) {
        html += `<button onclick="loadProducts(${pagination.current_page + 1})">Next</button>`;
    }

    container.innerHTML = html;
}

let searchTimeout;
function searchProducts() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadProducts(1);
    }, 500);
}

function openProductModal() {
    document.getElementById('modal-title').textContent = 'Add New Product';
    document.getElementById('product-form').reset();
    document.getElementById('product-id').value = '';
    existingProductImages = [];
    removedProductImageIds = [];
    selectedProductImages = [];
    document.getElementById('product-images').value = '';
    renderProductImagesPreview();

    // ✅ AUTO-TICK collection options và featured
    document.getElementById('product-collection-options').checked = true;
    document.getElementById('product-featured').checked = false;

    document.getElementById('product-sku').value = '';
    const catSelect = document.getElementById('product-category');
    catSelect.onchange = async function() {
        const catId = catSelect.value;
        if (catId) {
            try {
                const res = await fetch(getApiUrl('/api/index.php?request=products/nextid'));
                const data = await res.json();
                const nextId = data.next_id || 1;
                document.getElementById('product-sku').value = `DT-${catId}-${nextId}`;
            } catch (e) {
                console.error('Error getting next ID:', e);
                document.getElementById('product-sku').value = `DT-${catId}-???`;
            }
        } else {
            document.getElementById('product-sku').value = '';
        }
    };

    document.getElementById('product-modal').classList.add('active');
}

async function editProduct(id) {
    try {
        const apiPath = `/api/index.php?request=products/${id}`;
        const response = await fetch(getApiUrl(apiPath));
        const responseText = await response.text();
        const data = JSON.parse(responseText);
        
        // ✅ Handle both response formats
        const product = data.data || data;

        document.getElementById('modal-title').textContent = 'Edit Product';
        document.getElementById('product-id').value = product.id;
        document.getElementById('product-name').value = product.name;
        document.getElementById('product-sku').value = product.sku;
        document.getElementById('product-category').value = product.category_id;
        document.getElementById('product-description').value = product.description || '';
        document.getElementById('product-price').value = product.price;
        document.getElementById('product-stock').value = product.stock_quantity;
        document.getElementById('product-condition').value = product.condition_type;
        document.getElementById('product-status').value = product.is_active;

        existingProductImages = Array.isArray(product.images) ? product.images : [];
        removedProductImageIds = [];
        selectedProductImages = [];
        document.getElementById('product-images').value = '';
        renderProductImagesPreview();

        // ✅ Load collection options and featured from database
        document.getElementById('product-collection-options').checked = 
            product.show_collection_options == 1 || product.show_collection_options === true;
        document.getElementById('product-featured').checked = 
            product.is_featured == 1 || product.is_featured === true;

        document.getElementById('product-modal').classList.add('active');
    } catch (error) {
        console.error('Error loading product:', error);
        alert('Error loading product: ' + error.message);
    }
}

function closeProductModal() {
    document.getElementById('product-modal').classList.remove('active');
}



async function saveProduct(event) {
    event.preventDefault();

    const id = document.getElementById('product-id').value;
    const formData = new FormData();
    
    // Add text fields
    formData.append('name', document.getElementById('product-name').value.trim());
    formData.append('sku', document.getElementById('product-sku').value.trim());
    formData.append('category_id', document.getElementById('product-category').value);
    formData.append('description', document.getElementById('product-description').value.trim());
    formData.append('price', document.getElementById('product-price').value);
    formData.append('stock_quantity', document.getElementById('product-stock').value);
    formData.append('condition_type', document.getElementById('product-condition').value);
    formData.append('is_active', document.getElementById('product-status').value);
    formData.append('show_collection_options', document.getElementById('product-collection-options').checked ? '1' : '0');
    formData.append('is_featured', document.getElementById('product-featured').checked ? '1' : '0');

    // Add new images
    if (selectedProductImages.length > 0) {
        selectedProductImages.forEach((file) => {
            formData.append('product_images[]', file);
        });
    }
    
    // Add removed image ids
    if (removedProductImageIds.length > 0) {
        formData.append('removed_image_ids', JSON.stringify(removedProductImageIds));
    }

    // Log FormData for debugging
    console.log('Saving product with data:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + (pair[1] instanceof File ? pair[1].name : pair[1]));
    }

    try {
        const apiPath = id ? `/api/index.php?request=products/${id}` : '/api/index.php?request=products';
        const url = getApiUrl(apiPath);

        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const responseText = await response.text();
        const result = JSON.parse(responseText);
        
        if (response.ok && result.success !== false) {
            alert(id ? 'Product updated successfully!' : 'Product created successfully!');
            closeProductModal();
            loadProducts(currentPage);
        } else {
            console.error('Save error:', result);
            alert('Error: ' + (result.error || result.message || 'Failed to save product'));
        }
    } catch (error) {
        console.error('Error saving product:', error);
        alert('Error saving product: ' + error.message);
    }
}

async function deleteProduct(id, name) {
    const confirmed = await showConfirm(`Are you sure you want to delete "${name}"? You can undo this within 10 seconds.`, 'Delete Product', true);
    if (!confirmed) return;

    try {
        // Get product data before deletion for undo
        const apiPath = `/api/index.php?request=products/${id}`;
        const productResponse = await fetch(getApiUrl(apiPath));
        const productData = await productResponse.json();
        const originalProduct = productData.data || productData;

        const response = await fetch(getApiUrl(apiPath), {
            method: 'DELETE'
        });

        if (response.ok) {
            // Store action for undo
            lastBulkAction = {
                action: 'delete',
                products: [{
                    id: id,
                    fullData: originalProduct
                }]
            };
            
            showUndoBar('delete', 1);
            loadProducts(currentPage);
        } else {
            const responseText = await response.text();
            const error = JSON.parse(responseText);
            alert('Error deleting product: ' + (error.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error deleting product:', error);
        alert('Error: ' + error.message);
    }
}

async function loadCategories() {
    try {
        const response = await fetch(getApiUrl('/api/index.php?request=categories'));
        const responseText = await response.text();
        const data = JSON.parse(responseText);
        const categories = data.data || data;

        const filterSelect = document.getElementById('filter-category');
        const formSelect = document.getElementById('product-category');

        const options = categories.map(cat => 
            `<option value="${cat.id}">${cat.name}</option>`
        ).join('');

        filterSelect.innerHTML = '<option value="">All Categories</option>' + options;
        formSelect.innerHTML = '<option value="">Select Category</option>' + options;
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadProducts();
});

// If already loaded, run immediately
if (document.readyState === 'loading') {
    // Still loading, wait for DOMContentLoaded
} else {
    // Already loaded
    loadCategories();
    loadProducts();
}
</script>
        </main>
    </div>
    <?php include '../components/toast-notification.php'; ?>
</body>
</html>
