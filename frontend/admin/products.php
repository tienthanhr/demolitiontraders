<?php
session_start();

// Check if user is admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    header('Location: ../admin-login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Demolition Traders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php include 'admin-style.css'; ?>
    </style>
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
    </div>

    <div class="table-container">
        <table id="products-table">
            <thead>
                <tr>
                    <th>Image</th>
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
let currentPage = 1;
let totalPages = 1;

// Load products
async function loadProducts(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('products-tbody');
    tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px;"><div class="spinner"></div><p>Loading products...</p></td></tr>';

    try {
        const search = document.getElementById('search-products').value;
        const category = document.getElementById('filter-category').value;
        const condition = document.getElementById('filter-condition').value;

        let url = `/demolitiontraders/backend/api/index.php?request=products&page=${page}&per_page=20`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (category) url += `&category=${category}`;
        if (condition) url += `&condition=${condition}`;

        const response = await fetch(url);
        const data = await response.json();

        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px;">No products found</td></tr>';
            return;
        }

        tbody.innerHTML = data.data.map(product => `
            <tr>
                <td>
                    <img src="${product.image || 'assets/images/no-image.jpg'}" alt="${product.name}" class="product-img">
                </td>
                <td><strong>${product.sku}</strong></td>
                <td>${product.name.substring(0, 50)}${product.name.length > 50 ? '...' : ''}</td>
                <td>${product.category_name || 'N/A'}</td>
                <td><strong>$${parseFloat(product.price).toFixed(2)}</strong></td>
                <td>${product.stock_quantity}</td>
                <td>
                    <span class="badge badge-${product.condition_type}">${product.condition_type}</span>
                </td>
                <td>
                    <span class="badge badge-${product.is_active == 1 ? 'active' : 'inactive'}">
                        ${product.is_active == 1 ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
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

        // Update pagination
        if (data.pagination) {
            totalPages = data.pagination.total_pages;
            updatePagination(data.pagination);
        }
    } catch (error) {
        console.error('Error loading products:', error);
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: red;">Error loading products</td></tr>';
    }
}

// Update pagination
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

// Search products
let searchTimeout;
function searchProducts() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadProducts(1);
    }, 500);
}

// Open modal
function openProductModal() {
    document.getElementById('modal-title').textContent = 'Add New Product';
    document.getElementById('product-form').reset();
    document.getElementById('product-id').value = '';
    document.getElementById('product-modal').classList.add('active');
}

// Close modal
function closeProductModal() {
    document.getElementById('product-modal').classList.remove('active');
}

// Edit product
async function editProduct(id) {
    try {
        const response = await fetch(`/demolitiontraders/backend/api/index.php?request=products/${id}`);
        const product = await response.json();

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

        document.getElementById('product-modal').classList.add('active');
    } catch (error) {
        alert('Error loading product: ' + error.message);
    }
}

// Save product
async function saveProduct(event) {
    event.preventDefault();

    const id = document.getElementById('product-id').value;
    const data = {
        name: document.getElementById('product-name').value,
        sku: document.getElementById('product-sku').value,
        category_id: document.getElementById('product-category').value,
        description: document.getElementById('product-description').value,
        price: document.getElementById('product-price').value,
        stock_quantity: document.getElementById('product-stock').value,
        condition_type: document.getElementById('product-condition').value,
        is_active: document.getElementById('product-status').value
    };

    try {
        const url = id 
            ? `/demolitiontraders/backend/api/index.php?request=products/${id}`
            : `/demolitiontraders/backend/api/index.php?request=products`;
        
        const response = await fetch(url, {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            alert(id ? 'Product updated successfully!' : 'Product created successfully!');
            closeProductModal();
            loadProducts(currentPage);
        } else {
            const error = await response.json();
            alert('Error: ' + (error.error || 'Failed to save product'));
        }
    } catch (error) {
        alert('Error saving product: ' + error.message);
    }
}

// Delete product
async function deleteProduct(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"?`)) return;

    try {
        const response = await fetch(`/demolitiontraders/backend/api/index.php?request=products/${id}`, {
            method: 'DELETE'
        });

        if (response.ok) {
            alert('Product deleted successfully!');
            loadProducts(currentPage);
        } else {
            alert('Error deleting product');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Load categories for dropdown
async function loadCategories() {
    try {
        const response = await fetch('/demolitiontraders/backend/api/index.php?request=categories');
        const data = await response.json();
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
loadCategories();
loadProducts();
</script>
        </main>
    </div>
</body>
</html>
