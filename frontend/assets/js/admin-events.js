/**
 * Admin Events Handler
 * CSP Compliant - Removes all inline onclick/onchange/onsubmit handlers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Category modal opener
    const openCategoryBtn = document.querySelector('[data-action="open-category-modal"]');
    if (openCategoryBtn) {
        openCategoryBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openCategoryModal();
        });
    }

    // Checkbox select-all handler
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            toggleSelectAll(this);
        });
    }

    // Sortable table headers
    const sortHeaders = document.querySelectorAll('th[data-sortable="true"]');
    sortHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.style.userSelect = 'none';
        header.addEventListener('click', function() {
            const sortKey = header.getAttribute('data-sort-key');
            if (sortKey) {
                sortTable(sortKey);
            }
        });
    });

    // Category checkboxes
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActions();
        });
    });

    // Bulk action buttons
    const applyBulkBtn = document.querySelector('[data-action="apply-bulk"]');
    if (applyBulkBtn) {
        applyBulkBtn.addEventListener('click', function(e) {
            e.preventDefault();
            applyBulkAction();
        });
    }

    const clearSelectionBtn = document.querySelector('[data-action="clear-selection"]');
    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearSelection();
        });
    }

    // Undo action button
    const undoBtn = document.querySelector('[data-action="undo"]');
    if (undoBtn) {
        undoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            undoLastAction();
        });
    }

    const dismissUndoBtn = document.querySelector('[data-action="dismiss-undo"]');
    if (dismissUndoBtn) {
        dismissUndoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            dismissUndo();
        });
    }

    // Sort select handler
    const sortSelect = document.getElementById('sort-by');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            sortCategories();
        });
    }

    // Modal close buttons
    const closeButtons = document.querySelectorAll('[data-action="close-modal"]');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = btn.getAttribute('data-modal-id') || 'category-modal';
            closeModal(modalId);
        });
    });

    // Category form submit
    const categoryForm = document.getElementById('category-form');
    if (categoryForm) {
        categoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveCategory(e);
        });
    }

    // Edit/Delete category buttons
    setupCategoryRowHandlers();

    // Form password visibility toggle
    setupPasswordVisibilityToggles();
});

/**
 * Setup category row action handlers
 */
function setupCategoryRowHandlers() {
    const editButtons = document.querySelectorAll('[data-action="edit-category"]');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const categoryId = btn.getAttribute('data-category-id');
            editCategory(categoryId);
        });
    });

    const deleteButtons = document.querySelectorAll('[data-action="delete-category"]');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const categoryId = btn.getAttribute('data-category-id');
            const categoryName = btn.getAttribute('data-category-name');
            deleteCategory(categoryId, categoryName);
        });
    });
}

/**
 * Setup password visibility toggle
 */
function setupPasswordVisibilityToggles() {
    const toggleButtons = document.querySelectorAll('[data-action="toggle-password"]');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const inputId = btn.getAttribute('data-input-id');
            const toggleId = btn.getAttribute('data-toggle-id');
            togglePasswordVisibility(inputId, toggleId);
        });
    });
}

/**
 * Toggle password visibility
 */
function togglePasswordVisibility(inputId, toggleId) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    
    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        toggle?.classList.remove('fa-eye');
        toggle?.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        toggle?.classList.remove('fa-eye-slash');
        toggle?.classList.add('fa-eye');
    }
}

/**
 * Close modal by ID
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Placeholder functions - implement based on your needs
 */
function openCategoryModal() {
    const modal = document.getElementById('category-modal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function toggleSelectAll(checkbox) {
    const allCheckboxes = document.querySelectorAll('.category-checkbox');
    allCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

function updateBulkActions() {
    // Implementation
}

function applyBulkAction() {
    // Implementation
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.category-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('select-all').checked = false;
    updateBulkActions();
}

function undoLastAction() {
    // Implementation
}

function dismissUndo() {
    // Implementation
}

function sortCategories() {
    // Implementation
}

function sortTable(sortKey) {
    // Implementation
}

function saveCategory(e) {
    // Implementation
}

function editCategory(categoryId) {
    // Implementation
}

function deleteCategory(categoryId, categoryName) {
    // Implementation
}
