/**
 * Toast & Modal Events Handler
 * CSP Compliant - Removes all inline onclick handlers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Confirm modal buttons
    setupConfirmModalHandlers();
    
    // Toast close buttons
    setupToastCloseHandlers();
    
    // Overlay click handlers (modal close on overlay click)
    setupOverlayClickHandlers();
});

/**
 * Setup confirm modal handlers
 */
function setupConfirmModalHandlers() {
    const cancelBtn = document.querySelector('[data-action="confirm-cancel"]');
    const confirmBtn = document.querySelector('[data-action="confirm-ok"]');
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.confirmModalResolve) {
                window.confirmModalResolve(false);
            }
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.confirmModalResolve) {
                window.confirmModalResolve(true);
            }
        });
    }
}

/**
 * Setup toast close handlers
 */
function setupToastCloseHandlers() {
    // Use event delegation since toasts are dynamic
    document.addEventListener('click', function(e) {
        const closeBtn = e.target.closest('.toast-close');
        if (closeBtn) {
            const toast = closeBtn.closest('.toast');
            if (toast) {
                toast.remove();
            }
        }
    });
}

/**
 * Setup overlay click handlers
 */
function setupOverlayClickHandlers() {
    document.addEventListener('click', function(e) {
        const overlay = e.target;
        
        // Check if clicked element is an overlay with close-on-click
        if (overlay.id && overlay.id.includes('overlay')) {
            if (overlay.getAttribute('data-close-on-click') !== 'false') {
                overlay.style.display = 'none';
            }
        }
    });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info', duration = 3000) {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icon = getToastIcon(type);
    
    toast.innerHTML = `
        <div class="toast-content">
            ${icon}
            <span>${escapeHtml(message)}</span>
        </div>
        <button class="toast-close" aria-label="Close notification">×</button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove after duration
    if (duration > 0) {
        setTimeout(() => {
            toast.remove();
        }, duration);
    }
    
    return toast;
}

/**
 * Get toast icon based on type
 */
function getToastIcon(type) {
    const icons = {
        'success': '<i class="fa-solid fa-check-circle"></i>',
        'error': '<i class="fa-solid fa-exclamation-circle"></i>',
        'warning': '<i class="fa-solid fa-warning"></i>',
        'info': '<i class="fa-solid fa-info-circle"></i>'
    };
    return icons[type] || '';
}

/**
 * Create toast container if it doesn't exist
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 400px;
    `;
    document.body.appendChild(container);
    return container;
}

/**
 * Show confirm modal dialog
 */
function showConfirm(title, message, onConfirm, onCancel) {
    const modal = document.getElementById('confirm-modal-overlay');
    if (!modal) {
        console.error('Confirm modal not found');
        return;
    }

    const titleEl = modal.querySelector('.confirm-modal-title');
    const messageEl = modal.querySelector('.confirm-modal-message');
    
    if (titleEl) titleEl.textContent = title;
    if (messageEl) messageEl.textContent = message;

    // Store the callback
    window.confirmModalResolve = (confirmed) => {
        modal.style.display = 'none';
        if (confirmed && onConfirm) {
            onConfirm();
        } else if (!confirmed && onCancel) {
            onCancel();
        }
    };

    modal.style.display = 'block';
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export for external use
window.showToast = showToast;
window.showConfirm = showConfirm;
